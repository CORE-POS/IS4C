@echo off

set CC=csc /platform:x86 /nologo /nowarn:1607
set HID_SHARP_FILES=ReportDescriptors\GlobalItemTag.cs ReportDescriptors\CollectionType.cs ReportDescriptors\ItemType.cs ReportDescriptors\MainItemTag.cs ReportDescriptors\LocalItemTag.cs ReportDescriptors\Parser\ReportSegment.cs ReportDescriptors\Parser\ReportType.cs ReportDescriptors\Parser\LocalIndexes.cs ReportDescriptors\Parser\ReportMainItem.cs ReportDescriptors\Parser\IndexList.cs ReportDescriptors\Parser\Report.cs ReportDescriptors\Parser\IndexBase.cs ReportDescriptors\Parser\ReportDescriptorParser.cs ReportDescriptors\Parser\IndexRange.cs ReportDescriptors\Parser\ReportCollection.cs ReportDescriptors\EncodedItem.cs ReportDescriptors\Units\TimeUnit.cs ReportDescriptors\Units\Unit.cs ReportDescriptors\Units\UnitSystem.cs ReportDescriptors\Units\TemperatureUnit.cs ReportDescriptors\Units\LuminousIntensityUnit.cs ReportDescriptors\Units\CurrentUnit.cs ReportDescriptors\Units\MassUnit.cs ReportDescriptors\Units\LengthUnit.cs ReportDescriptors\DataMainItemFlags.cs HidDevice.cs Throw.cs AsyncResult.cs Properties\AssemblyInfo.cs HidStream.cs HidDeviceLoader.cs Platform\HidManager.cs Platform\MacOS\MacHidManager.cs Platform\MacOS\MacHidStream.cs Platform\MacOS\MacHidDevice.cs Platform\MacOS\NativeMethods.cs Platform\Utf8Marshaler.cs Platform\HidSelector.cs Platform\Windows\WinHidManager.cs Platform\Windows\WinHidDevice.cs Platform\Windows\WinHidStream.cs Platform\Windows\NativeMethods.cs Platform\Unsupported\UnsupportedHidManager.cs Platform\Linux\LinuxHidStream.cs Platform\Linux\LinuxHidManager.cs Platform\Linux\LinuxHidDevice.cs Platform\Linux\NativeMethods.cs

REM Auto detect whether PDCX DLLs preset
set PDCX_LIBS=
set PDCX_FILES=
if exist DSIPDCXLib.dll (
    if exist AxDSIPDCXLib.dll (
        set PDCX_LIBs=/r:DSIPDCXLib.dll /r:AxDSIPDCXLib.dll
	set PDCX_FILES=SPH_Datacap_PDCX.cs
    )
)
set RABBITMQ=
if exist rabbitmq\RabbitMQ.Client.dll (
    set RABBITMQ=/r:rabbitmq\RabbitMQ.Client.dll
)
@echo on

%CC% /target:library /out:DelegateForm.dll DelegateForm.cs
%CC% /target:library /out:Bitmap.dll BitmapConverter.cs
%CC% /target:library /out:ParallelLayer.dll ParallelLayer.cs Parallel-Win32.cs
cd HidSharp
%CC% /target:library /unsafe /out:HIDSharp.dll %HID_SHARP_FILES%
cd ..
copy HidSharp\HIDSharp.dll .
%CC% /target:library /out:USBLayer.dll USBLayer.cs USB-Win32.cs
%CC% /target:library /out:USBLayerFuture.dll /r:HIDSharp.dll USBLayer.cs USB-HidSharp.cs
%CC% /target:library /r:DelegateForm.dll /out:UDPMsgBox.dll UDPMsgBox.cs

%CC% /target:library ^
/r:DelegateForm.dll /r:Bitmap.dll /r:USBLayer.dll /r:ParallelLayer.dll %PDCX_LIBS% ^
/out:SPH.dll ^
SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_IngenicoRBA_RS232.cs SPH_SignAndPay_USB.cs SPH_IngenicoRBA_USB.cs SPH_IngenicoRBA_Common.cs SPH_IngenicoRBA_IP.cs SPH_Parallel_Writer.cs %PDCX_FILES%

%CC% /target:library ^
/r:DelegateForm.dll /r:Bitmap.dll /r:USBLayerFuture.dll /r:ParallelLayer.dll %PDCX_LIBS% ^
/define:FUTURE /define:FUTUREWIN ^
/out:SPHFuture.dll ^
SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_IngenicoRBA_RS232.cs SPH_SignAndPay_USB.cs SPH_IngenicoRBA_USB.cs SPH_IngenicoRBA_Common.cs SPH_IngenicoRBA_IP.cs SPH_Parallel_Writer.cs %PDCX_FILES%

%CC% /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll %RABBITMQ% /out:pos.exe Magellan.cs
%CC% /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPHFuture.dll %RABBITMQ% /out:pos-future.exe Magellan.cs
@echo off
REM csc /target:library /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /out:Magellan.dll Magellan.cs
REM csc /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /r:Magellan.dll /out:posSVC.exe MagellanWinSVC.cs
