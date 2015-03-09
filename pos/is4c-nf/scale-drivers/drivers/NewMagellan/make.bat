set CC=csc /platform:x86 /nologo

%CC% /target:library /out:DelegateForm.dll DelegateForm.cs
%CC% /target:library /out:Bitmap.dll BitmapConverter.cs
%CC% /target:library /out:USBLayer.dll USBLayer.cs USB-Win32.cs
%CC% /target:library /r:DelegateForm.dll /out:UDPMsgBox.dll UDPMsgBox.cs

@echo off
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

%CC% /target:library ^
/r:DelegateForm.dll /r:Bitmap.dll /r:USBLayer.dll %PDCX_LIBS% ^
/out:SPH.dll ^
SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_IngenicoRBA_RS232.cs SPH_SignAndPay_USB.cs SPH_IngenicoRBA_USB.cs SPH_IngenicoRBA_Common.cs SPH_IngenicoRBA_IP.cs %PDCX_FILES%

%CC% /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll %RABBITMQ% /out:pos.exe Magellan.cs
@echo off
REM csc /target:library /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /out:Magellan.dll Magellan.cs
REM csc /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /r:Magellan.dll /out:posSVC.exe MagellanWinSVC.cs
