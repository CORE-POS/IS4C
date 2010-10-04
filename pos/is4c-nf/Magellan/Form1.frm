VERSION 5.00
Object = "{EAB22AC0-30C1-11CF-A7EB-0000C05BAE0B}#1.1#0"; "shdocvw.dll"
Object = "{648A5603-2C6E-101B-82B6-000000000014}#1.1#0"; "MSCOMM32.OCX"
Begin VB.Form Form1 
   BorderStyle     =   0  'None
   Caption         =   "Form1"
   ClientHeight    =   6810
   ClientLeft      =   0
   ClientTop       =   0
   ClientWidth     =   7005
   LinkTopic       =   "Form1"
   ScaleHeight     =   6810
   ScaleWidth      =   7005
   ShowInTaskbar   =   0   'False
   StartUpPosition =   3  'Windows Default
   Begin MSCommLib.MSComm MSComm1 
      Left            =   240
      Top             =   240
      _ExtentX        =   1005
      _ExtentY        =   1005
      _Version        =   393216
      DTREnable       =   -1  'True
   End
   Begin SHDocVwCtl.WebBrowser WebBrowser1 
      Height          =   855
      Left            =   960
      TabIndex        =   0
      Top             =   360
      Width           =   735
      ExtentX         =   1296
      ExtentY         =   1508
      ViewMode        =   0
      Offline         =   0
      Silent          =   0
      RegisterAsBrowser=   0
      RegisterAsDropTarget=   1
      AutoArrange     =   0   'False
      NoClientEdge    =   0   'False
      AlignLeft       =   0   'False
      NoWebView       =   0   'False
      HideFileNames   =   0   'False
      SingleClick     =   0   'False
      SingleSelection =   0   'False
      NoFolders       =   0   'False
      Transparent     =   0   'False
      ViewID          =   "{0057D0E0-3573-11CF-AE69-08002B2E1262}"
      Location        =   ""
   End
End
Attribute VB_Name = "Form1"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
'  Copyright 2001, 2004 Wedge Community Co-op

'    This file is part of IS4C.

'    IS4C is free software; you can redistribute it and/or modify
'    it under the terms of the GNU General Public License as published by
'    the Free Software Foundation; either version 2 of the License, or
'    (at your option) any later version.

'    IS4C is distributed in the hope that it will be useful,
'    but WITHOUT ANY WARRANTY; without even the implied warranty of
'    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
'    GNU General Public License for more details.

'    You should have received a copy of the GNU General Public License
'    in the file license.txt along with IS4C; if not, write to the Free Software
'    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA



Public shellReturn
Public InBuffer
Public intI
Public sendKeyFlag
Public strLocation
Public intJ
Public intK
Public intMov
Public beeped
Public one44
Public intZero
Public repoll
Public onefourone
Public elevenReceived





Private Sub Form_Load()


         Me.Caption = "Integrated Systems for Co-operatives"
         Form1.Width = Screen.Width
         Form1.Height = Screen.Height
         Form1.Left = 0
         Form1.Top = 0
         

         WebBrowser1.Navigate "localhost"
         
         WebBrowser1.Width = Screen.Width
         WebBrowser1.Height = Screen.Height
         WebBrowser1.Left = 0
         WebBrowser1.Top = 0

         intK = 0
         intMov = 0
         beeped = 0
         repoll = 1
         onefourone = 0
         elevenReceived = 0
         InBuffer = ""
         preBuffer = ""
         ReDim aWeight(1)
         aWeight(0) = 0
         intI = 0
         sendKeyFlag = 0
         ReDim Preserve aWeight(intI + 1)
         With MSComm1
            .CommPort = 1
            .Handshaking = 2 - comRTS
            .RThreshold = 1
            .RTSEnable = True
            .Settings = "9600,o,7,1"
            .SThreshold = 1
            .PortOpen = True
            ' Leave all other settings as default values.
         End With
         Do
         clearbuffer = MSComm1.Input
            If Len(clearbuffer) < 1 Then Exit Do
         Loop
         

End Sub

      Private Sub Form_Unload(Cancel As Integer)
      
        On Error GoTo error_handler
        
        InBuffer = ""
        Do
        clearbuffer = MSComm1.Input
            If Len(clearbuffer) < 1 Then Exit Do
        Loop
         MSComm1.PortOpen = False
         
error_handler:
         
      End Sub


      Private Sub MSComm1_OnComm()
      
        On Error GoTo error_handler
        
         If MSComm1.CommEvent = comEvReceive Then
                   preBuffer = MSComm1.Input
                   
                   If Len(preBuffer) < 8 Then
                        preBuffer = Left(preBuffer & "        ", 8)
                   End If
                    

                   InBuffer = InBuffer & preBuffer

                   If intK = 1 Then
                        intB = InStr(InBuffer, Chr$(13))


                        If intB > 0 Then
                        
                            aInBuffer = Split(InBuffer, Chr$(13))
                            InBuffer = aInBuffer(0)
                        
                            intA = InStr(InBuffer, "S08A")
                            intE = InStr(InBuffer, "S08E")
                            intF = InStr(InBuffer, "S08F")
                            If intA > 0 Then
                                intScan = intA
                            ElseIf intE > 0 Then
                                intScan = intE
                            ElseIf intF > 0 Then
                                intScan = intF
                            Else
                                intScan = 0
                            End If
                            
                            If intScan > 0 Then
                                InBuffer = Mid(InBuffer, intScan, Len(InBuffer))
                            End If
                            
                            If Left(InBuffer, 1) = "S" Then
                            
                                Call HandleInBuffer(InBuffer)
                                
                            Else
                                InBuffer = ""
                                MSComm1.Output = "S11" & Chr$(13)
                            End If
                            
                            InBuffer = ""

                        End If

                   End If
                   

   

      End If
error_handler:
   
      End Sub

      Sub HandleInBuffer(InBuffer)
       BytesIn = InBuffer
       InBuffer = ""
       strPrefix = Left(BytesIn, 4)

      
        On Error GoTo error_handler

        Dim dblWeight


        If strPrefix = "S08A" And InStr(strLocation, "List") = 0 And InStr(strLocation, "Search") = 0 Then
            
            If WebBrowser1.Document.frames(1).Document.Hidden.scan.Value <> "noScan" Then
                WebBrowser1.Document.frames(0).Document.All("reginput").Value = WebBrowser1.Document.frames(0).Document.All("reginput").Value & Mid(BytesIn, 5, 11) & Chr$(13)
                WebBrowser1.Document.frames(0).Document.All("form").submit



            End If
            'WebBrowser1.Refresh
        ElseIf strPrefix = "S08F" And InStr(strLocation, "List") = 0 And InStr(strLocation, "Search") = 0 Then
            If WebBrowser1.Document.frames(1).Document.Hidden.scan.Value <> "noScan" Then
                WebBrowser1.Document.frames(0).Document.All("reginput").Value = WebBrowser1.Document.frames(0).Document.All("reginput").Value & Mid(BytesIn, 5, 12) & Chr$(13)
                WebBrowser1.Document.frames(0).Document.All("form").submit


            End If
        ElseIf strPrefix = "S08E" And InStr(strLocation, "List") = 0 And InStr(strLocation, "Search") = 0 Then
            If WebBrowser1.Document.frames(1).Document.Hidden.scan.Value <> "noScan" Then
                upcE = Mid(BytesIn, 5, 7)
                P6 = Right(upcE, 1)
                If P6 = 0 Then
                    upcE = Left(upcE, 3) & "00000" & Mid(upcE, 4, 3)
                ElseIf P6 = 1 Then
                    upcE = Left(upcE, 3) & "10000" & Mid(upcE, 4, 3)
                ElseIf P6 = 2 Then
                    upcE = Left(upcE, 3) & "20000" & Mid(upcE, 4, 3)
                ElseIf P6 = 3 Then
                    upcE = Left(upcE, 4) & "00000" & Mid(upcE, 5, 2)
                ElseIf P6 = 4 Then
                    upcE = Left(upcE, 5) & "00000" & Mid(upcE, 6, 1)
                Else
                    upcE = Left(upcE, 6) & "0000" & P6
                End If
            
                WebBrowser1.Document.frames(0).Document.All("reginput").Value = WebBrowser1.Document.frames(0).Document.All("reginput").Value & upcE & Chr$(13)
                WebBrowser1.Document.frames(0).Document.All("form").submit


            
            End If
        
            
        ElseIf Left(BytesIn, 3) = "S11" Then
            onefourone = 0
            'WebBrowser1.Document.frames(2).Document.All("reginput").Value = Left(BytesIn, 7) & Chr$(13)
            'WebBrowser1.Document.frames(2).Document.All("form").submit
            intZero = 0
            If elevenSent = 0 Then
                intMov = 0
            End If
            'Call timeDelay(0.2)
            MSComm1.Output = "S14" & Chr$(13)
            
        ElseIf strPrefix = "S143" Then
            onefourone = 0
            WebBrowser1.Document.frames(2).Document.All("reginput").Value = "S110000" & Chr$(13)
            WebBrowser1.Document.frames(2).Document.All("form").submit
            sendKeyFlag = 1
  
            elevenSent = 0
            MSComm1.Output = "S11" & Chr$(13)
        
 
        ElseIf strPrefix = "S141" Then
            elevenSent = 0
            If onefourone = 0 Then

                WebBrowser1.Document.frames(2).Document.All("reginput").Value = "S141" & Chr$(13)
                WebBrowser1.Document.frames(2).Document.All("form").submit
            End If
            onefourone = 1
            intMove = 0
            Call timeDelay(0.2)
            MSComm1.Output = "S14" & Chr$(13)
            
        ElseIf strPrefix = "S145" Or strPrefix = "S142" Then
            onefourone = 0
            elevenSent = 0
            WebBrowser1.Document.frames(2).Document.All("reginput").Value = strPrefix & Chr$(13)
            WebBrowser1.Document.frames(2).Document.All("form").submit
            
            Call timeDelay(0.2)
            MSComm1.Output = "S11" & Chr$(13)
            
        ElseIf strPrefix = "S144" Then
        
            If Abs(CInt(Mid(BytesIn, 5, 4)) - one44) > 1 Or (CInt(Mid(BytesIn, 5, 4)) - one44) = -1 Or onefourone = 1 Then
                intMov = 0
            End If
            one44 = CInt(Mid(BytesIn, 5, 4))
            onefourone = 0
            If intMov = 0 Then

                intMov = 1
                WebBrowser1.Document.frames(2).Document.All("reginput").Value = "S11" & Mid(BytesIn, 5, 4) & Chr$(13)
                WebBrowser1.Document.frames(2).Document.All("form").submit
                elevenSent = 1
            End If
            Call timeDelay(0.2)
            MSComm1.Output = "S14" & Chr$(13)
            
        Else
            onefourone = 0
            elevenSent = 0
            Call timeDelay(0.2)
            MSComm1.Output = "S11" & Chr$(13)
        
        End If
        BytesIn = ""
    
error_handler:
        BytesIn = ""

End Sub



Private Sub WebBrowser1_DownloadComplete()

    'WebBrowser1.SetFocus

End Sub

Private Sub WebBrowser1_NavigateComplete2(ByVal pDisp As Object, URL As Variant)
    On Error GoTo error_handler
    
    WebBrowser1.SetFocus
    strLocation = URL

    If URL = "http://localhost/bye.html" Then
        Unload Form1
    End If
    
error_handler:

End Sub

Public Sub timeDelay(sngDelay As Single)
On Error GoTo error_handler

Const cSecondsInDay = 86400        ' # of seconds in a day.

Dim sngStart As Single             ' Start time.
Dim sngStop  As Single             ' Stop time.
Dim sngNow   As Single             ' Current time.

sngStart = Timer                   ' Get current timer.
sngStop = sngStart + sngDelay      ' Set up stop time based on
                                   ' delay.

Do
    sngNow = Timer                 ' Get current timer again.
    If sngNow < sngStart Then      ' Has midnight passed?
        sngStop = sngStart - cSecondsInDay  ' If yes, reset end.
    End If
    DoEvents                       ' Let OS process other events.

Loop While sngNow < sngStop        ' Has time elapsed?

error_handler:

End Sub

Private Sub WebBrowser1_DocumentComplete(ByVal pDisp As Object, URL1 As Variant)
        On Error GoTo error_handler
        
        If (InStr(URL1, "pos") > 0 Or InStr(URL1, "product") > 0) Or InStr(URL1, "boxMsg") > 0 Or InStr(URL1, "qtty") > 0 Then
            Dim Beepstatus
            Beepstatus = WebBrowser1.Document.frames(1).Document.Hidden.alert.Value
            scResetStatus = WebBrowser1.Document.frames(1).Document.Hidden.scReset.Value
            
            If Beepstatus = "errorBeep" Then
                    Call beeps(3)

            ElseIf Beepstatus = "beepTwice" Then
                    Call beeps(2)
                    
            ElseIf Beepstatus = "goodBeep" Then
                    Call beeps(1)
                    
            ElseIf Beepstatus = "twoPairs" Then
                    Call timeDelay(0.3)
                    Call beeps(2)
                    Call timeDelay(0.3)
                    Call beeps(2)

            ElseIf Beepstatus = "rePoll" Or scResetStatus = "rePoll" Then
                    intMov = 0
                    elevenSent = 0
                    Call timeDelay(0.25)
                    MSComm1.Output = "S14" & Chr$(13)

            End If
        End If
       
        If (pDisp Is WebBrowser1.Object) Then
            intK = 1
            intMov = 0

        End If
        
error_handler:
        

End Sub

Private Sub beeps(ByVal beepNum)

On Error GoTo error_handler

intBeepN = 1
    MSComm1.Output = "S334" & Chr$(13)
While intBeepN < beepNum
    Call timeDelay(0.15)
    MSComm1.Output = "S334" & Chr$(13)
    intBeepN = intBeepN + 1
Wend

error_handler:

End Sub



