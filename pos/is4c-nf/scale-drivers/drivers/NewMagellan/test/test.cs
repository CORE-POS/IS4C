using System;
using System.IO;
using System.Drawing;
using System.Windows.Forms;

class FramesTest : Form {
	private WebBrowser wb;

	public FramesTest(){
		int width = 800;
		int height = 600;
		this.Size = new Size(width,height);

		wb = new WebBrowser();
		wb.Parent = this;
		wb.Size = new Size(width,height);
		wb.Url = new Uri(Path.GetFullPath("frames.html"));
		wb.DocumentCompleted += new WebBrowserDocumentCompletedEventHandler(PrintStatus);
	}

	public void PrintStatus(object sender, WebBrowserDocumentCompletedEventArgs e){
		HtmlWindow currWin = wb.Document.Window;
		HtmlWindowCollection frames = currWin.Frames;
		System.Console.WriteLine("Frame count: "+frames.Count);	
	}

	[STAThread]
	static public void Main(){
		Application.Run(new FramesTest());
	}	
}
