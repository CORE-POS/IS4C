CC=gmcs
RM=rm -f
BASE_LIBS=-r:System.Drawing -r:System.Windows.Forms

all: DelegateForm.dll SPH.dll Magellan.cs
	$(CC) -target:exe $(BASE_LIBS) -r:SPH.dll -r:DelegateForm.dll -out:pos.exe Magellan.cs

DelegateForm.dll: DelegateBrowserForm.cs
	$(CC) -target:library $(BASE_LIBS) -out:DelegateForm.dll DelegateBrowserForm.cs

SPH.dll: DelegateForm.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_Ingenico_i6550.cs
	$(CC) -target:library $(BASE_LIBS) -r:DelegateForm.dll -out:SPH.dll SerialPortHandler.cs SPH_Magellan_Scale.cs SPH_Ingenico_i6550.cs

clean:
	$(RM) pos.exe SPH.dll DelegateForm.dll
