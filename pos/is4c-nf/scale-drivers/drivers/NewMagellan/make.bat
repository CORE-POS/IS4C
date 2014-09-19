csc /target:library /out:DelegateForm.dll DelegateBrowserForm.cs DelegateForm.cs
csc /target:library /out:Bitmap.dll BitmapConverter.cs
csc /target:library /out:USBLayer.dll USBLayer.cs USB-Win32.cs
csc /target:library /out:ParallelLayer.dll ParallelLayer.cs Parallel-Win32.cs
csc /target:library /r:DelegateForm.dll /out:UDPMsgBox.dll UDPMsgBox.cs
csc /target:library /r:DelegateForm.dll /r:Bitmap.dll /r:USBLayer.dll /r:ParallelLayer.cs /out:SPH.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_Ingenico_i6550.cs SPH_SignAndPay_USB.cs SPH_Parallel_Writer.cs
csc /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /out:pos.exe Magellan.cs
csc /target:library /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /out:Magellan.dll Magellan.cs
csc /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /r:Magellan.dll /out:posSVC.exe MagellanWinSVC.cs
