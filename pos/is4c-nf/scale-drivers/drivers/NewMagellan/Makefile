CC=gmcs
RM=rm -f
BASE_LIBS=

all: DelegateForm.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:pos.exe Magellan.cs

UDPMsgBox.dll: DelegateForm.dll UDPMsgBox.cs
	$(CC) -target:library $(BASE_LIBS) -r:DelegateForm.dll -out:UDPMsgBox.dll UDPMsgBox.cs

DelegateForm.dll: DelegateForm.cs
	$(CC) -target:library $(BASE_LIBS) -out:DelegateForm.dll DelegateForm.cs

SPH.dll: DelegateForm.dll SerialPortHandler.cs SPH_Magellan_Scale.cs 
	$(CC) -target:library $(BASE_LIBS) -r:DelegateForm.dll -out:SPH.dll SerialPortHandler.cs SPH_Magellan_Scale.cs

Magellan.dll: DelegateForm.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:library $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:Magellan.dll Magellan.cs

posSVC.exe: Magellan.dll MagellanWinSVC.cs
	$(CC) -target:exe $(BASE_LIBS) -r:System.ServiceProcess.dll -r:System.Configuration.Install.dll -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -r:Magellan.dll -out:posSVC.exe MagellanWinSVC.cs

clean:
	$(RM) pos.exe SPH.dll DelegateForm.dll UDPMsgBox.dll
