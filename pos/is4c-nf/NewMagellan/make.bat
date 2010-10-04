csc /target:library /out:DelegateForm.dll DelegateBrowserForm.cs DelegateForm.cs
csc /target:library /r:DelegateForm.dll /out:UDPMsgBox.dll UDPMsgBox.cs
csc /target:library /r:DelegateForm.dll /out:SPH.dll SerialPortHandler.cs SPH_Magellan_Scale.cs 
csc /target:exe /r:DelegateForm.dll /r:UDPMsgBox.dll /r:SPH.dll /out:pos.exe Magellan.cs
