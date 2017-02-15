namespace FPH

open System
open System.IO
open System.IO.Ports
open System.Threading
open System.Collections.Generic

open SPH

type FPH_Magellan_Scale(p) =
    inherit SerialPortHandler(p)

    let sp = new SerialPort(
                PortName=p,
                BaudRate=9600,
                DataBits=7,
                StopBits=StopBits.One,
                Parity=Parity.Odd,
                RtsEnable=true,
                Handshake=Handshake.None,
                ReadTimeout=500)

    let rec beep num =
        match num with
        | 0 -> ()
        | _ ->
            sp.Write("S334\r")
            System.Threading.Thread.Sleep(150);
            beep (num-1)

    let expandUPCE (upc:string) =
        let lead = upc.Substring(0, upc.Length-1)
        let tail = upc.Substring(upc.Length-1)
        match tail with
        | "0" | "1" | "2" ->
            (lead.Substring(0,3)) + tail + "0000" + (lead.Substring(3))
        | "3" ->
            (lead.Substring(0,4)) + tail + "00000" + (lead.Substring(4))
        | "4" ->
            (lead.Substring(0,5)) + tail + "00000" + (lead.Substring(5))
        | _ ->
            lead + "0000" + tail

    let parseData (s:string) =
        match (s.Substring(0, 2)) with
        | "S0" ->
            let barcode = 
                match (s.Substring(0, 4)) with
                | "S08A" | "S08F" -> 
                    s.Substring(4)
                | "S08E" ->
                    expandUPCE (s.Substring(4))
                | "S08R" ->
                    "GS1~" + s.Substring(3)
                | "S08B" ->
                    match (s.Substring(0, 5)) with
                    | "S08B1" | "S08B2" | "S08B3" ->
                        s.Substring(5)
                    | _ -> ""
                | _ -> ""
            (barcode, "")
        | "S1" ->
            (s, "S14\r")
        | _ -> (s, "")

    let byteEvent = new Event<_>()
    [<CLIEvent>]
    member this.ByteEvent = byteEvent.Publish

    member this.ParentMsg msg =
        this.parent.MsgSend(msg)

    override this.Read() = 
        sp.Open |> ignore
        let rec readLoop()  = 
            try
                let b = sp.ReadByte()
                byteEvent.Trigger(b)
            with
            | ex -> printfn "%s" (ex.ToString())

            readLoop()

        let byteStream = this.ByteEvent
        let asyncRead = async {
            readLoop()
        }

        let listBuilder (carry:list<_>) (prev, cur) = 
            match carry.IsEmpty with
            | true -> [cur; prev]
            | false  -> 
                match prev with
                | 13 -> [cur]
                | _ -> cur :: carry

        byteStream
        |> Observable.pairwise
        |> Observable.scan listBuilder []
        |> Observable.filter (fun bytes -> bytes.Head = 13)
        |> Observable.map (fun bytes -> bytes.Tail)
        |> Observable.map (fun bytes -> (List.fold (fun acc b -> ((char b).ToString()) + acc) "" bytes))
        |> Observable.subscribe (fun msg -> 
            let pos,scale = parseData msg
            match pos.Length with
            | 0 -> ()
            | _ -> this.ParentMsg(pos)
            match scale.Length with
            | 0 -> ()
            | _ -> sp.Write(scale)
            ) 
            |> ignore

        Async.RunSynchronously asyncRead
        ()

    override this.HandleMsg msg = 
        match msg with
        | "errorBeep" -> beep 3
        | "beepTwice" -> beep 2
        | "goodBeep" -> beep 1
        | "twoPairs" ->
            beep 2
            System.Threading.Thread.Sleep(300)
            beep 2
        | "rePoll" ->
            sp.Write("S14\r")
            ()
        | _ -> ()
