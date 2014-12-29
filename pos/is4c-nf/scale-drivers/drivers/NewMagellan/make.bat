set CC=csc /platform:x86 /nologo

%CC% /target:library /out:DelegateForm.dll DelegateBrowserForm.cs DelegateForm.cs
%CC% /target:library /out:Bitmap.dll BitmapConverter.cs
%CC% /target:library /out:USBLayer.dll USBLayer.cs USB-Win32.cs
%CC% /target:library /r:DelegateForm.dll /out:UDPMsgBox.dll UDPMsgBox.cs

%CC% /target:library ^
/r:DelegateForm.dll /r:Bitmap.dll /r:USBLayer.dll /r:DSIPDCXLib.dll /r:AxDSIPDCXLib.dll ^
/out:SPH.dll ^
SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_Ingenico_i6550.cs SPH_SignAndPay_USB.cs SPH_IngenicoRBA_USB.cs SPH_Datacap_PDCX.cs

%CC% /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /out:pos.exe Magellan.cs
@echo off
REM csc /target:library /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /out:Magellan.dll Magellan.cs
REM csc /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /r:Magellan.dll /out:posSVC.exe MagellanWinSVC.cs
