CC=gmcs
RM=rm -f
BASE_LIBS=

all: DelegateForm.dll USBLayer.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:pos.exe Magellan.cs

UDPMsgBox.dll: DelegateForm.dll UDPMsgBox.cs
	$(CC) -target:library $(BASE_LIBS) -r:DelegateForm.dll -out:UDPMsgBox.dll UDPMsgBox.cs

DelegateForm.dll: DelegateForm.cs
	$(CC) -target:library $(BASE_LIBS) -out:DelegateForm.dll DelegateForm.cs

USBLayer.dll: USBLayer.cs USB-Posix.cs
	$(CC) -target:library $(BASE_LIBS) -r:Mono.Posix.dll -out:USBLayer.dll USBLayer.cs USB-Posix.cs

SPH.dll: DelegateForm.dll USBLayer.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs
	$(CC) -define:MONO -target:library $(BASE_LIBS) -r:DelegateForm.dll -r:USBLayer.dll -out:SPH.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs

Magellan.dll: DelegateForm.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:library $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:Magellan.dll Magellan.cs

posSVC.exe: Magellan.dll MagellanWinSVC.cs
	$(CC) -target:exe $(BASE_LIBS) -r:System.ServiceProcess.dll -r:System.Configuration.Install.dll -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -r:Magellan.dll -out:posSVC.exe MagellanWinSVC.cs

bmpcon.exe: BitmapConverter.cs
	$(CC) -target:exe $(BASE_LIBS) -out:bmpcon.exe BitmapConverter.cs

clean:
	$(RM) pos.exe SPH.dll DelegateForm.dll UDPMsgBox.dll USBLayer.dll
