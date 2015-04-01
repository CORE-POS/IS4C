CC=gmcs
RM=rm -f
BASE_LIBS=/r:System.Drawing.dll

all: DelegateForm.dll USBLayer.dll ParallelLayer.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:pos.exe Magellan.cs

future: DelegateForm.dll USBLayerFuture.dll ParallelLayer.dll SPHFuture.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPHFuture.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:pos.exe Magellan.cs

socket.exe: DelegateForm.dll USBLayer.dll ParallelLayer.dll SPH.dll UDPMsgBox.dll WebSockets.dll NewerMagellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -r:WebSockets.dll -out:socket.exe NewerMagellan.cs

UDPMsgBox.dll: DelegateForm.dll UDPMsgBox.cs
	$(CC) -target:library $(BASE_LIBS) -r:DelegateForm.dll -out:UDPMsgBox.dll UDPMsgBox.cs

DelegateForm.dll: DelegateForm.cs
	$(CC) -target:library $(BASE_LIBS) -out:DelegateForm.dll DelegateForm.cs

USBLayer.dll: USBLayer.cs USB-Posix.cs
	$(CC) -target:library $(BASE_LIBS) -r:Mono.Posix.dll -out:USBLayer.dll USBLayer.cs USB-Posix.cs

USBLayerFuture.dll: HIDSharp.dll USBLayer.cs USB-HidSharp.cs
	$(CC) -target:library $(BASE_LIBS) -r:Mono.Posix.dll -r:HidSharp/HIDSharp.dll -out:USBLayerFuture.dll USBLayer.cs USB-HidSharp.cs

ParallelLayer.dll: ParallelLayer.cs Parallel-Posix.cs
	$(CC) -target:library $(BASE_LIBS) -r:Mono.Posix.dll -out:ParallelLayer.dll ParallelLayer.cs Parallel-Posix.cs

WebSockets.dll: WebSocketServer.cs
	$(CC) -target:library $(BASE_LIBS) -out:WebSockets.dll WebSocketServer.cs

Bitmap.dll: BitmapConverter.cs
	$(CC) -target:library $(BASE_LIBS) -out:Bitmap.dll BitmapConverter.cs

HIDSharp.dll:
	cd HidSharp; $(MAKE) $(MFLAGS)
	cp HidSharp/HIDSharp.dll .

SPH.dll: DelegateForm.dll USBLayer.dll ParallelLayer.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs \
		SPH_Parallel_Writer.cs SPH_IngenicoRBA_Common.cs SPH_IngenicoRBA_IP.cs \
		SPH_IngenicoRBA_USB.cs SPH_IngenicoRBA_RS232.cs \
		Bitmap.dll
	$(CC) -define:MONO -target:library $(BASE_LIBS) \
		-r:DelegateForm.dll -r:USBLayer.dll -r:ParallelLayer.dll -r:Bitmap.dll \
		-out:SPH.dll \
		SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs SPH_Parallel_Writer.cs SPH_IngenicoRBA_IP.cs \
		SPH_IngenicoRBA_USB.cs SPH_IngenicoRBA_RS232.cs SPH_IngenicoRBA_Common.cs

SPHFuture.dll: DelegateForm.dll USBLayer.dll ParallelLayer.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs \
		SPH_Parallel_Writer.cs SPH_IngenicoRBA_Common.cs SPH_IngenicoRBA_IP.cs \
		SPH_IngenicoRBA_USB.cs SPH_IngenicoRBA_RS232.cs \
		Bitmap.dll
	$(CC) -define:FUTURE -target:library $(BASE_LIBS) \
        -r:HidSharp/HIDSharp.dll \
		-r:DelegateForm.dll -r:USBLayerFuture.dll -r:ParallelLayer.dll -r:Bitmap.dll \
		-out:SPHFuture.dll \
		SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_SignAndPay_USB.cs SPH_Parallel_Writer.cs SPH_IngenicoRBA_IP.cs \
		SPH_IngenicoRBA_USB.cs SPH_IngenicoRBA_RS232.cs SPH_IngenicoRBA_Common.cs

Magellan.dll: DelegateForm.dll SPH.dll UDPMsgBox.dll Magellan.cs
	$(CC) -target:library $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -out:Magellan.dll Magellan.cs

posSVC.exe: Magellan.dll MagellanWinSVC.cs
	$(CC) -target:exe $(BASE_LIBS) -r:System.ServiceProcess.dll -r:System.Configuration.Install.dll -r:SPH.dll -r:DelegateForm.dll -r:UDPMsgBox.dll -r:Magellan.dll -out:posSVC.exe MagellanWinSVC.cs

bmpcon.exe: BitmapConverter.cs
	$(CC) -target:exe $(BASE_LIBS) -out:bmpcon.exe BitmapConverter.cs

wstest.exe: WebSocketServer.cs
	$(CC) -target:exe $(BASE_LIBS) -out:wstest.exe WebSocketServer.cs

clean:
	$(RM) pos.exe SPH.dll SPHFuture.dll DelegateForm.dll UDPMsgBox.dll USBLayer.dll USBLayerFuture.dll Bitmap.dll ParallelLayer.dll WebSockets.dll HIDSharp.dll
