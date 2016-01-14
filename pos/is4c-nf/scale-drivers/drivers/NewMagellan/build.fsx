// include Fake lib
#r @"packages/FAKE/tools/FakeLib.dll"
open Fake
open CscHelper
open FscHelper
open EnvironmentHelper
open FileSystemHelper
open MSBuildHelper
open FileUtils

type AllArch = { c: CscPlatform; f: FscPlatform; }

Target "Default" (fun _ ->

    // invoke msbuild on another project
    build (fun p -> p) "./HidSharp/HidSharp.csproj" |> ignore
    rm "./Newtonsoft.Json.dll" |> ignore
    build (fun p -> p) "./Newtonsoft.Json/Newtonsoft.Json.Net40.csproj" |> ignore

    let emvxRef = ["DSIEMVXLib.dll"; "AxDSIEMVXLib.dll"]
    let emvx = allFilesExist emvxRef
    let pdcxRef = ["DSIPDCXLib.dll"; "AxDSIPDCXLib.dll"]
    let pdcx = allFilesExist pdcxRef

    // set architecture if 32-bit activeX control wrappers are present
    let arch = match pdcx with
               | true -> { c=CscPlatform.X86; f=FscPlatform.X86; }
               | false -> { c=CscPlatform.AnyCpu; f=FscPlatform.AnyCpu; }
    let archParams p:CscParams = { p with Platform=arch.c }
    let cscLib p = { (archParams p) with Target=CscTarget.Library }

    // invoke c# compiler
    ["DelegateForm.cs"] 
    |> Csc (fun p -> { (cscLib p) with Output="DelegateForm.dll" })

    ["Discover.cs"] 
    |> Csc (fun p -> { (cscLib p) with Output="Discover.dll" })

    // platform-specific library reference
    let bitmapRef = match isMono with
                    | true -> ["System.Drawing.dll"]
                    | false -> []
    ["BitmapConverter.cs"; "Signature.cs"]
    |> Csc (fun p -> { (cscLib p) with Output="Bitmap.dll"; References=bitmapRef })

    // platform-specific files
    let pllFiles = match isMono with
                   | true -> "Parallel-Posix.cs"
                   | false -> "Parallel-Win32.cs"
    ["ParallelLayer.cs"; pllFiles]
    |> Csc (fun p -> { (cscLib p) with Output="ParallelLayer.dll" })

    let usb = match isMono with
              | true -> "USB-Posix.cs"
              | false -> "USB-Win32.cs"
    ["USBLayer.cs"; "USB-HidSharp.cs"; usb]
    |> Csc (fun p -> { (cscLib p) with Output="USBLayer.dll"; References=["HidSharp.dll"] })

    ["UDPMsgBox.cs"] 
    |> Csc (fun p -> { (cscLib p) with Output="UDPMsgBox.dll"; References=["DelegateForm.dll"] })

    let sphAlways = [
        "SerialPortHandler.cs";
        "SPH_Magellan_Scale.cs";
        "SPH_SignAndPay_USB.cs";
        "SPH_SignAndPay_Auto.cs";
        "SPH_SignAndPay_Native.cs";
        "SPH_IngenicoRBA_Common.cs";
        "SPH_IngenicoRBA_RS232.cs";
        "SPH_IngenicoRBA_IP.cs";
        "SPH_IngenicoRBA_USB.cs";
        "SPH_Parallel_Writer.cs";
        "SPH_Datacap_IPTran.cs";
    ]
    let sphFiles =
        match pdcx,emvx with
        | true,true -> ["SPH_Datacap_PDCX.cs"; "SPH_Datacap_EMVX.cs"] @ sphAlways
        | true,false -> "SPH_Datacap_PDCX.cs" :: sphAlways
        | _ -> sphAlways

    let sphRefAlways = ["DelegateForm.dll"; "Bitmap.dll"; "USBLayer.dll"; "ParallelLayer.dll"]
    let sphRef =
        match isMono,pdcx,emvx with
        | true,false,false -> "System.Drawing.dll" :: sphRefAlways
        | false,true,true -> pdcxRef @ emvxRef @ sphRefAlways
        | false,true,false -> pdcxRef @ sphRefAlways
        | _ -> sphRefAlways

    let sphDef = match isMono with
                 | true -> ["/define:Mono"]
                 | false -> []

    sphFiles 
    |> Csc (fun p -> { (cscLib p) with Output="SPH.dll"; References=sphRef; OtherParams=sphDef })

    // Use Fsc to compile F# code
    ["FPH_Magellan_Scale.fs"]
    |> Fsc (fun p -> { p with Output="FPH.dll"; Platform=arch.f; FscTarget=FscTarget.Library; References=["SPH.dll"] })

    let rabbit = fileExists "RabbitMQ.Client.dll"
    let exeDll = ["DelegateForm.dll"; "UDPMsgBox.dll"; "SPH.dll"; "Discover.dll"; "Newtonsoft.Json.dll"]
    let exeRef = match rabbit with
                 | true -> "RabbitMQ.Client.dll" :: exeDll
                 | false -> exeDll
    let exeOther = match rabbit with
                   | true -> ["/define:CORE_RABBIT"; "/define:NEWTONSOFT_JSON"]
                   | false -> ["/define:NEWTONSOFT_JSON"]
    ["Magellan.cs"] 
    |> Csc (fun p -> { (archParams p) with Output="pos.exe"; References=exeRef; Target=CscTarget.Exe; OtherParams=exeOther })
)

RunTargetOrDefault "Default"

