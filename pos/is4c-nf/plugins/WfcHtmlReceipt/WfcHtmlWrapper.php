<?php
/*******************************************************************************

 Copyright 2015 Whole Foods Co-op.

 This file is part of IT CORE.

 IT CORE is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 IT CORE is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 in the file license.txt along with IT CORE; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*********************************************************************************/

class WfcHtmlWrapper extends DefaultHtmlEmail
{
    public function receiptHeader()
    {
        return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <!-- NAME: POP-UP -->
 <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Whole Foods Co-op</title>

 <style type="text/css">
 body,#bodyTable,#bodyCell{
 height:100% !important;
 margin:0;
 padding:0;
 width:100% !important;
 }
 table{
 border-collapse:collapse;
 }
 img,a img{
 border:0;
 outline:none;
 text-decoration:none;
 }
 h1,h2,h3,h4,h5,h6{
 margin:0;
 padding:0;
 }
 p{
 margin:1em 0;
 padding:0;
 }
 a{
 word-wrap:break-word;
 }
 .ReadMsgBody{
 width:100%;
 }
 .ExternalClass{
 width:100%;
 }
 .ExternalClass,.ExternalClass p,.ExternalClass span,.ExternalClass font,.ExternalClass td,.ExternalClass div{
 line-height:100%;
 }
 table,td{
 mso-table-lspace:0pt;
 mso-table-rspace:0pt;
 }
 #outlook a{
 padding:0;
 }
 img{
 -ms-interpolation-mode:bicubic;
 }
 body,table,td,p,a,li,blockquote{
 -ms-text-size-adjust:100%;
 -webkit-text-size-adjust:100%;
 }
 #bodyCell{
 padding:0;
 }
 .mcnImage{
 vertical-align:bottom;
 }
 .mcnTextContent img{
 height:auto !important;
 }
 a.mcnButton{
 display:block;
 }
 body,#bodyTable{
 background-color:#F5F5F5;
 }
 #bodyCell{
 border-top:0;
 }
 h1{
 color:#ff5800 !important;
 display:block;
 font-family:Helvetica;
 font-size:30px;
 font-style:normal;
 font-weight:bold;
 line-height:200%;
 letter-spacing:1px;
 margin:0;
 text-align:center;
 }
 h2{
 color:#ffffff !important;
 display:block;
 font-family:Helvetica;
 font-size:26px;
 font-style:normal;
 font-weight:bold;
 line-height:125%;
 letter-spacing:normal;
 margin:0;
 text-align:left;
 }
 h3{
 color:#404040 !important;
 display:block;
 font-family:Helvetica;
 font-size:18px;
 font-style:normal;
 font-weight:bold;
 line-height:125%;
 letter-spacing:normal;
 margin:0;
 text-align:left;
 }
 h4{
 color:#606060 !important;
 display:block;
 font-family:Helvetica;
 font-size:16px;
 font-style:normal;
 font-weight:bold;
 line-height:125%;
 letter-spacing:normal;
 margin:0;
 text-align:left;
 }
 #templatePreheader{
 background-color:#ff5800;
 border-top:0;
 border-bottom:0;
 }
 #preheaderBackground{
 background-color:#ff5800;
 border-top:0;
 border-bottom:0;
 }
 .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
 color:#FFFFFF;
 font-family:Helvetica;
 font-size:10px;
 line-height:125%;
 text-align:left;
 }
 .preheaderContainer .mcnTextContent a{
 color:#FFFFFF;
 font-weight:normal;
 text-decoration:underline;
 }
 #templateHeader{
 background-color:#ff5800;
 border-top:0;
 border-bottom:0;
 }
 #headerBackground{
 background-color:#FFFFFF;
 border-top:0;
 border-bottom:0;
 }
 .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
 color:#202020;
 font-family:Helvetica;
 font-size:16px;
 line-height:150%;
 text-align:left;
 }
 .headerContainer .mcnTextContent a{
 color:#92c728;
 font-weight:normal;
 text-decoration:underline;
 }
 #templateBody{
 background-color:#f5f5f5;
 border-top:0;
 border-bottom:0;
 }
 #bodyBackground{
 background-color:#FFFFFF;
 border-top:0;
 border-bottom:0;
 }
 .bodyContainer .mcnTextContent,.bodyContainer .mcnTextContent p{
 color:#202020;
 font-family:Helvetica;
 font-size:18px;
 line-height:150%;
 text-align:center;
 }
 .bodyContainer .mcnTextContent a{
 color:#92c728;
 font-weight:bold;
 text-decoration:underline;
 }
 #templateFooter{
 background-color:#eee4d1;
 border-top:0;
 border-bottom:22px solid #eee4d1;
 }
 #footerBackground{
 background-color:#FFFFFF;
 border-top:0;
 border-bottom:0;
 }
 .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
 color:#606060;
 font-family:Helvetica;
 font-size:10px;
 line-height:125%;
 text-align:center;
 }
 .footerContainer .mcnTextContent a{
 color:#606060;
 font-weight:normal;
 text-decoration:underline;
 }
 @media only screen and (max-width: 480px){
 body,table,td,p,a,li,blockquote{
 -webkit-text-size-adjust:none !important;
 }

 } 
 @media only screen and (max-width: 480px){
 body{
 width:100% !important;
 min-width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=mcnTextContentContainer]{
 width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=mcnBoxedTextContentContainer]{
 width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=mcpreview-image-uploader]{
 width:100% !important;
 display:none !important;
 }

 }
 @media only screen and (max-width: 480px){
 img[class=mcnImage]{
 width:100% !important;
 }

 }
 @media only screen and (max-width: 480px){
 table[class=mcnImageGroupContentContainer]{
 width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageGroupContent]{
 padding:9px !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageGroupBlockInner]{
 padding-bottom:0 !important;
 padding-top:0 !important;
 }
 }
 @media only screen and (max-width: 480px){
 tbody[class=mcnImageGroupBlockOuter]{
 padding-bottom:9px !important;
 padding-top:9px !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=mcnCaptionTopContent],table[class=mcnCaptionBottomContent]{
 width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=mcnCaptionLeftTextContentContainer],table[class=mcnCaptionRightTextContentContainer],table[class=mcnCaptionLeftImageContentContainer],table[class=mcnCaptionRightImageContentContainer],table[class=mcnImageCardLeftTextContentContainer],table[class=mcnImageCardRightTextContentContainer]{
 width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageCardLeftImageContent],td[class=mcnImageCardRightImageContent]{
 padding-right:18px !important;
 padding-left:18px !important;
 padding-bottom:0 !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageCardBottomImageContent]{
 padding-bottom:9px !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageCardTopImageContent]{
 padding-top:18px !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageCardLeftImageContent],td[class=mcnImageCardRightImageContent]{
 padding-right:18px !important;
 padding-left:18px !important;
 padding-bottom:0 !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageCardBottomImageContent]{
 padding-bottom:9px !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnImageCardTopImageContent]{
 padding-top:18px !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=mcnCaptionLeftContentOuter] td[class=mcnTextContent],table[class=mcnCaptionRightContentOuter] td[class=mcnTextContent]{
 padding-top:9px !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnCaptionBlockInner] table[class=mcnCaptionTopContent]:last-child td[class=mcnTextContent]{
 padding-top:18px !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnBoxedTextContentColumn]{
 padding-left:18px !important;
 padding-right:18px !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=mcnTextContent]{
 padding-right:18px !important;
 padding-left:18px !important;
 }
 }
 @media only screen and (max-width: 480px){
 img[class=flexibleImage]{
 height:auto !important;
 width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=templateContainer]{
 max-width:600px !important;
 width:100% !important;
 }
 }
 @media only screen and (max-width: 480px){
 h1{
 font-size:24px !important;
 line-height:125% !important;
 }
 }
 @media only screen and (max-width: 480px){
 h2{
 font-size:20px !important;
 line-height:125% !important;
 }
 }
 @media only screen and (max-width: 480px){
 h3{
 font-size:18px !important;
 line-height:125% !important;
 }
 }
 @media only screen and (max-width: 480px){
 h4{
 font-size:16px !important;
 line-height:125% !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[class=mcnBoxedTextContentContainer] td[class=mcnTextContent],td[class=mcnBoxedTextContentContainer] td[class=mcnTextContent] p{
 font-size:18px !important;
 line-height:125% !important;
 }
 }
 @media only screen and (max-width: 480px){
 table[id=templatePreheader]{
 display:block !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=preheaderContainer] td[class=mcnTextContent],td[class=preheaderContainer] td[class=mcnTextContent] p{
 font-size:14px !important;
 line-height:115% !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=headerContainer] td[class=mcnTextContent],td[class=headerContainer] td[class=mcnTextContent] p{
 font-size:18px !important;
 line-height:125% !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=bodyContainer] td[class=mcnTextContent],td[class=bodyContainer] td[class=mcnTextContent] p{
 font-size:18px !important;
 line-height:125% !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=footerContainer] td[class=mcnTextContent],td[class=footerContainer] td[class=mcnTextContent] p{
 font-size:14px !important;
 line-height:115% !important;
 }
 }
 @media only screen and (max-width: 480px){
 td[class=footerContainer] a[class=utilityLink]{
 display:block !important;
 }
 }
 </style>
 </head>
 <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin: 0;padding: 0;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #F5F5F5;height: 100% !important;width: 100% !important;">
 <center>
 <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;margin: 0;padding: 0;background-color: #F5F5F5;height: 100% !important;width: 100% !important;">
 <tr>
 <td align="center" valign="top" id="bodyCell" style="padding-bottom: 40px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;margin: 0;padding: 0;border-top: 0;height: 100% !important;width: 100% !important;">
 <!-- BEGIN TEMPLATE // -->
 <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <!-- BEGIN PREHEADER // -->
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="templatePreheader" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #ff5800;border-top: 0;border-bottom: 0;">
 <tr>
 <td align="center" valign="top" style="padding-right: 10px;padding-left: 10px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="600" class="templateContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="preheaderBackground" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #ff5800;border-top: 0;border-bottom: 0;">
 <tr>
 <td valign="top" class="preheaderContainer" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnTextBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnTextBlockOuter">
 <tr>
 <td class="mcnTextBlockInner" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnTextContentContainer" align="left" border="0" cellpadding="0" cellspacing="0" width="282" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnTextContent" style="padding-top: 9px;padding-left: 18px;padding-bottom: 9px;padding-right: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color: #FFFFFF;font-size: 10px;line-height: 125%;text-align: left;" valign="top">
 <div style="text-align: left;"><span style="font-size:10px">&nbsp;</span></div>
 </td>
 </tr>
 </tbody>
 </table> <!-- mcnTextContentContainer -->
 <table class="mcnDividerBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnDividerBlockOuter">
 <tr>
 <td class="mcnDividerBlockInner" style="padding: 6px 18px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <span></span>
 </td>
 </tr>
 </tbody>
 </table> <!-- class=mcnDividerContent -->
 </td>
 </tr>
 </tbody>
 </table> <!-- class=mcnDividerBlock -->
 </td>
 </tr>
 </tbody>
 </table> <!-- class=mcnTextBlock -->
 </td>
 </tr>
 </table> <!-- id=preheaderBackground -->
 </td>
 </tr>
 </table> <!-- class=templateContainer -->
 </td>
 </tr>
 </table> <!-- id=templatePreheader -->
 <!-- // END PREHEADER -->
 </td>
 </tr>
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <!-- BEGIN HEADER // -->
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="templateHeader" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #ff5800;border-top: 0;border-bottom: 0;">
 <tr>
 <td align="center" valign="top" style="padding-right: 10px;padding-left: 10px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="600" class="templateContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="headerBackground" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #FFFFFF;border-top: 0;border-bottom: 0;">
 <tr>
 <td valign="top" class="headerContainer" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnCaptionBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnCaptionBlockOuter">
 <tr>
 <td class="mcnCaptionBlockInner" style="padding: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" valign="top">
 <table class="mcnCaptionRightContentOuter" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnCaptionRightContentInner" style="padding: 0 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" valign="top">
 <table class="mcnCaptionRightImageContentContainer" align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnCaptionRightImageContent" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <a href="http://wholefoods.coop" title="" class="" target="_blank" style="word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <img alt="" src="http://wholefoods.coop/images/email/logo.png" style="max-width: 216px;border: 0;outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;vertical-align: bottom;" class="mcnImage" width="176">
 </a>
 </td>
 </tr>
 </tbody>
 </table> <!-- class=mcnCaptoinRightImageContentContainer -->
 <table class="mcnCaptionRightTextContentContainer" align="right" border="0" cellpadding="0" cellspacing="0" width="352" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="line-height: 200%;text-align: center;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color: #202020;font-size: 16px;" class="mcnTextContent" valign="top">
 <span style="color: #666666;font-size: 14px;line-height: 1.6em;">Whole Foods Co-op</span>
 <br>
 <span style="font-size:14px"><span style="font-size:46px"><span style="color:#ff5800"><strong><span style="line-height:54px">Receipt</span></strong></span></span></span>
 <br>
 </td>
 </tr>
 </tbody>
 </table> <!-- class=mcnCaptoinRightTextContentContainer -->
 </td>
 </tr>
 </tbody>
 </table> <!-- mcnCaptionRightContentOuter -->
 </td>
 </tr>
 </tbody>
 </table> <!-- class=mcnCaptionBlock -->
 </td>
 </tr>
 </table> <!-- id=headerBackground -->
 </td>
 </tr>
 </table> <!-- class=templateContainer -->
 </td>
 </tr>
 </table> <!-- id=templateHeader -->
 <!-- // END HEADER -->
 </td>
 </tr>
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <!-- BEGIN BODY // -->
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="templateBody" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #f5f5f5;border-top: 0;border-bottom: 0;">
 <tr>
 <td align="center" valign="top" style="padding-right: 10px;padding-left: 10px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="600" class="templateContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="bodyBackground" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #FFFFFF;border-top: 0;border-bottom: 0;">
 <tr>
 <td valign="top" class="bodyContainer" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnTextBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnTextBlockOuter">
 <tr>
 <td class="mcnTextBlockInner" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnTextContentContainer" align="left" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnTextContent" style="padding: 9px 18px;line-height: 150%;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color: #202020;font-size: 18px;text-align: center;" valign="top">
 <div style="text-align: left;"><span style="font-size:14px; line-height: 0.9em;">
HTML;
    }

    public function receiptFooter()
    {
        return <<<HTML
</span>
</div>
</td>
</tr>
</tbody>
</table>
</td>
</tbody>
</table>
<table class="mcnDividerBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<tbody class="mcnDividerBlockOuter">
<tr>
<td class="mcnDividerBlockInner" style="padding: 10px 18px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<tbody>
<tr>
<td style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<span></span>
</td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 <table class="mcnFollowBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnFollowBlockOuter">
 <tr>
 <td style="padding: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnFollowBlockInner" align="center" valign="top">
 <table class="mcnFollowContentContainer" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-left: 9px;padding-right: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="center">
 <table style="background-color: #FFFFFF;border: 1px none #EEEEEE;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnFollowContent" border="0" cellpadding="0" cellspacing="0" width="100%">
 <tbody>
 <tr>
 <td style="padding-top: 9px;padding-right: 9px;padding-left: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="center" valign="top">
 <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-right: 10px;padding-bottom: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnFollowContentItemContainer" valign="top">
 <table class="mcnFollowContentItem" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-top: 5px;padding-right: 10px;padding-bottom: 5px;padding-left: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <table align="left" border="0" cellpadding="0" cellspacing="0" width="" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnFollowIconContent" align="center" valign="middle" width="24" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <a href="https://www.facebook.com/pages/Whole-Foods-Co-op/49852317869" target="_blank" style="word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;"><img src="http://wholefoods.coop/images/email/color-facebook-48.png" style="display: block;border: 0;outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;" class="" height="24" width="24"></a>
 </td>
 <td class="mcnFollowTextContent" style="padding-left: 5px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <a href="https://www.facebook.com/pages/Whole-Foods-Co-op/49852317869" target="" style="font-size: 10px;text-decoration: none;color: #606060;font-weight: bold;line-height: 100%;text-align: center;word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">Facebook</a>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 <!--[if gte mso 6]>
 </td>
 <td align="left" valign="top">
 <![endif]-->
 <table align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-right: 10px;padding-bottom: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnFollowContentItemContainer" valign="top">
 <table class="mcnFollowContentItem" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-top: 5px;padding-right: 10px;padding-bottom: 5px;padding-left: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <table align="left" border="0" cellpadding="0" cellspacing="0" width="" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnFollowIconContent" align="center" valign="middle" width="24" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <a href="https://twitter.com/WFCduluth" target="_blank" style="word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;"><img src="http://wholefoods.coop/images/email/color-twitter-48.png" style="display: block;border: 0;outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;" class="" height="24" width="24"></a>
 </td>
 <td class="mcnFollowTextContent" style="padding-left: 5px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <a href="https://twitter.com/WFCduluth" target="" style="font-size: 10px;text-decoration: none;color: #606060;font-weight: bold;line-height: 100%;text-align: center;word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">Twitter</a>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 <!--[if gte mso 6]>
 </td>
 <td align="left" valign="top">
 <![endif]-->
 <table align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-right: 10px;padding-bottom: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnFollowContentItemContainer" valign="top">
 <table class="mcnFollowContentItem" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-top: 5px;padding-right: 10px;padding-bottom: 5px;padding-left: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <table align="left" border="0" cellpadding="0" cellspacing="0" width="" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnFollowIconContent" align="center" valign="middle" width="24" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <a href="http://www.pinterest.com/wholefoodscoop/" target="_blank" style="word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;"><img src="http://wholefoods.coop/images/email/color-pinterest-48.png" style="display: block;border: 0;outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;" class="" height="24" width="24"></a>
 </td>
 <td class="mcnFollowTextContent" style="padding-left: 5px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <a href="http://www.pinterest.com/wholefoodscoop/" target="" style="font-size: 10px;text-decoration: none;color: #606060;font-weight: bold;line-height: 100%;text-align: center;word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">Pinterest</a>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 <!--[if gte mso 6]>
 </td>
 <td align="left" valign="top">
 <![endif]-->
 <table align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-right: 0;padding-bottom: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnFollowContentItemContainer" valign="top">
 <table class="mcnFollowContentItem" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="padding-top: 5px;padding-right: 10px;padding-bottom: 5px;padding-left: 9px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <table align="left" border="0" cellpadding="0" cellspacing="0" width="" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnFollowIconContent" align="center" valign="middle" width="24" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <a href="http://wholefoods.coop" target="_blank" style="word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;"><img src="http://wholefoods.coop/images/email/color-link-48.png" style="display: block;border: 0;outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;" class="" height="24" width="24"></a>
 </td>
 <td class="mcnFollowTextContent" style="padding-left: 5px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" align="left" valign="middle">
 <a href="http://wholefoods.coop" target="" style="font-size: 10px;text-decoration: none;color: #606060;font-weight: bold;line-height: 100%;text-align: center;word-wrap: break-word;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">Website</a>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 <!--[if gte mso 6]>
 </td>
 <td align="left" valign="top">
 <![endif]-->
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </table>
 </td>
 </tr>
 </table>
 </td>
 </tr>
 </table>
 <!-- // END BODY -->
 </td>
 </tr>
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <!-- BEGIN FOOTER // -->
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="templateFooter" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #eee4d1;border-top: 0;border-bottom: 22px solid #eee4d1;">
 <tr>
 <td align="center" valign="top" style="padding-right: 10px;padding-left: 10px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="600" class="templateContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tr>
 <td align="center" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table border="0" cellpadding="0" cellspacing="0" width="100%" id="footerBackground" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: #FFFFFF;border-top: 0;border-bottom: 0;">
 <tr>
 <td valign="top" class="footerContainer" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnDividerBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnDividerBlockOuter">
 <tr>
 <td class="mcnDividerBlockInner" style="padding: 20px 18px 18px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table style="border-top: 1px dotted #808080;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%">
 <tbody>
 <tr>
 <td style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <span></span>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 <table class="mcnTextBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnTextBlockOuter">
 <tr>
 <td class="mcnTextBlockInner" valign="top" style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnTextContentContainer" align="left" border="0" cellpadding="0" cellspacing="0" width="366" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnTextContent" style="padding-top: 9px;padding-left: 18px;padding-bottom: 9px;padding-right: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color: #606060;font-size: 10px;line-height: 125%;text-align: center;" valign="top">
 <div style="text-align: left;"><span style="font-size:10px"><em>Copyright 2014 Whole Foods Co-op, All rights reserved.</em></span><br>
 </td>
 </tr>
 </tbody>
 </table>
 <table class="mcnTextContentContainer" align="right" border="0" cellpadding="0" cellspacing="0" width="197" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td class="mcnTextContent" style="padding-top: 9px;padding-right: 18px;padding-bottom: 9px;padding-left: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color: #606060;font-size: 10px;line-height: 125%;text-align: center;" valign="top">
 <div style="text-align: left;"><span style="font-size:10px"><strong>Our mailing address is:</strong><br>
 Whole Foods Co-op<br>
 610 East 4th Street<br>
 Duluth, MN 55803</span></div>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 <table class="mcnDividerBlock" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody class="mcnDividerBlockOuter">
 <tr>
 <td class="mcnDividerBlockInner" style="padding: 10px 18px;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <tbody>
 <tr>
 <td style="mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <span></span>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </tbody>
 </table>
 </td>
 </tr>
 </table>
 </td>
 </tr>
 </table>
 </td>
 </tr>
 </table>
 <!-- // END FOOTER -->
 </td>
 </tr>
 </table>
 <!-- // END TEMPLATE -->
 </td>
 </tr>
 </table>
 </center>
 </body>
 </html>
HTML;
    }
}

