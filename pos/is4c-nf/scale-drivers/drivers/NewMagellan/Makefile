CC=gmcs
RM=rm -f
BASE_LIBS=

all: DelegateForm.dll USBLayer.dll ParallelLayer.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:pos.exe Magellan.cs

socket.exe: DelegateForm.dll USBLayer.dll ParallelLayer.dll SPH.dll UDPMsgBox.dll WebSockets.dll NewerMagellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -r:WebSockets.dll -out:socket.exe NewerMagellan.cs

UDPMsgBox.dll: DelegateForm.dll UDPMsgBox.cs
	$(CC) -target:library $(BASE_LIBS) -r:DelegateForm.dll -out:UDPMsgBox.dll UDPMsgBox.cs

DelegateForm.dll: DelegateForm.cs
	$(CC) -target:library $(BASE_LIBS) -out:DelegateForm.dll DelegateForm.cs

USBLayer.dll: USBLayer.cs USB-Posix.cs
	$(CC) -target:library $(BASE_LIBS) -r:Mono.Posix.dll -out:USBLayer.dll USBLayer.cs USB-Posix.cs

ParallelLayer.dll: ParallelLayer.cs Parallel-Posix.cs
	$(CC) -target:library $(BASE_LIBS) -r:Mono.Posix.dll -out:ParallelLayer.dll ParallelLayer.cs Parallel-Posix.cs

WebSockets.dll: WebSocketServer.cs
	$(CC) -target:library $(BASE_LIBS) -out:WebSockets.dll WebSocketServer.cs

SPH.dll: DelegateForm.dll USBLayer.dll ParallelLayer.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs SPH_Parallel_Writer.cs SPH_iSC250_IP.cs
	$(CC) -define:MONO -target:library $(BASE_LIBS) \
        -r:DelegateForm.dll -r:USBLayer.dll -r:ParallelLayer \
        -out:SPH.dll \
        SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs SPH_Parallel_Writer.cs SPH_iSC250_IP.cs

Magellan.dll: DelegateForm.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:library $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:Magellan.dll Magellan.cs

posSVC.exe: Magellan.dll MagellanWinSVC.cs
	$(CC) -target:exe $(BASE_LIBS) -r:System.ServiceProcess.dll -r:System.Configuration.Install.dll -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -r:Magellan.dll -out:posSVC.exe MagellanWinSVC.cs

bmpcon.exe: BitmapConverter.cs
	$(CC) -target:exe $(BASE_LIBS) -out:bmpcon.exe BitmapConverter.cs

wstest.exe: WebSocketServer.cs
	$(CC) -target:exe $(BASE_LIBS) -out:wstest.exe WebSocketServer.cs

clean:
	$(RM) pos.exe SPH.dll DelegateForm.dll UDPMsgBox.dll USBLayer.dll
