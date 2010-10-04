CC=gmcs
RM=rm -f
BASE_LIBS=-r:System.Drawing -r:System.Windows.Forms

all: test.cs
	$(CC) -target:exe $(BASE_LIBS) -out:test.exe test.cs

clean:
	$(RM) test.exe
