/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/*************************************************************
 * DelegateBrowserForm
 *	A Form with a WebBrowser object and a set of delegates
 * to access than object. 
 *
 * Delegates:
 * string CheckDelegate(int FrameNumber, string FieldName)
 * 	returns "value" attribute of the given tag, generally
 * 	an <input>, in the given frame
 *
 * void SetDelegate(int FrameNumber, string FieldName, string FieldValue)
 * 	updates the "value" attribute of the given tag in
 * 	the given frame
 *
 * void SubmitDelegate(int FrameNumber, int FormNumber)
 * 	submits the given form in the given frame
 *
 * Uri UrlDelegate(int FrameNumber)
 * 	returns the Url currently loaded in the given frame. Since
 * 	frames aren't referenceable by name, this can help find
 * 	determine which frame is the one you want
 *
*************************************************************/
using System;
using System.Drawing;
using System.Windows.Forms;
using System.Threading;

namespace CustomForms {

public class DelegateBrowserForm : Form {

	public delegate string CheckValue(string FieldName);
	public CheckValue CheckDelegate;

	public delegate void SetValue(string FieldName, string NewValue);
	public SetValue SetDelegate;

	public delegate void SubmitForm(string FormName);
	public SubmitForm SubmitDelegate;

	public delegate void RunScript(string source);
	public RunScript ScriptDelegate;

	public delegate Uri GetUrl();
	public GetUrl UrlDelegate;

	public delegate void MsgRecv(string msg);
	public MsgRecv MsgDelegate;

	protected WebBrowser wb;

	public DelegateBrowserForm(){
		wb = new WebBrowser();

		CheckDelegate = new CheckValue(this.CheckValueMethod);
		SetDelegate = new SetValue(this.SetValueMethod);
		SubmitDelegate = new SubmitForm(this.SubmitFormMethod);
		ScriptDelegate = new RunScript(this.RunScriptMethod);
		UrlDelegate = new GetUrl(this.GetUrlMethod);
	}

	public string CheckValueMethod(string FieldName){
		HtmlDocument doc = wb.Document;
		if (doc == null) return "";

		HtmlElement tag = GetElementByName(doc, FieldName);
		if (tag == null) return "";

		return tag.GetAttribute("value");
	}

	public void SetValueMethod(string FieldName, string NewValue){
		HtmlDocument doc = wb.Document;
		if (doc != null){
			HtmlElement tag = GetElementByName(doc, FieldName);
			if (tag != null){
				tag.SetAttribute("value", NewValue);
			}
		}
	}

	public void SubmitFormMethod(string FormName){
		HtmlDocument doc = wb.Document;
		if (doc != null){
			HtmlElementCollection forms = doc.Forms;
			if (forms[FormName] != null){
				forms[FormName].InvokeMember("submit");
			}
		}
	}

	public void RunScriptMethod(string source){
		HtmlDocument doc = wb.Document;
		if (doc != null){
			doc.InvokeScript(source);	
		}
	}

	public Uri GetUrlMethod(){
		HtmlDocument doc = wb.Document;
		if (doc == null) return null;
		else return doc.Url;
	}

	protected HtmlElement GetElementByName(HtmlDocument doc, string name){
		HtmlElementCollection alltags = doc.All;
		HtmlElementCollection validtags = alltags.GetElementsByName(name);
		if (validtags.Count < 1) return null;
		else return validtags[0];
	}

}

}
