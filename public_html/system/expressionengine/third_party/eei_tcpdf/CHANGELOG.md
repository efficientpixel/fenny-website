# CHANGELOG #

## 0.1.0 (2011-11-01)

  * Implement support for using PDF templates via FPDI (see tag parameter "pdf-template") 
  * Create vendor folder to support 3rd parties lib. and add full FPDI package on it 
  * Updated the README(/USER GUIDE) file
  * Tested with TCPDF lib. version 5.9.133
  * Tested with FPDI version 1.4.2
  * Tested with FPDF_TPL version 1.2

## 0.0.7 (2011-10-29)

  * New tag "background-img" for PDF pages (just A4 at this moment) with texture (and yes, now it take cares about page orientation :-) 
  * Improve some checks on the plugin CP
  * Plugin filesystem reorganized using CI package convention
  * Fix some typos and errors on the README(/USER GUIDE) file
  * Tested with TCPDF lib. version 5.9.133

---

## 0.0.6 (2011-08-09)

  * New tags "fsave-path", "fsave-name" and "fsave-type" to manage file saving on server
    (please read the user guide for more info and how to use it)
  * Tested with TCPDF lib. version 5.9.107
                                                               
---

## 0.0.5 (2011-05-15)

  * Support for NSM Addon Updater
  * New tag "header" to disable page header
  * New tag "footer" to disable page footer
  * New tags "margin-left" and "margin-right" to specify the related values
  * New tags "allow-perms", "allow-pswd", "allow-owner" and "allow-mode" to create protected PDF file 
    (see http://www.tcpdf.org/doc/classTCPDF.html#a7ea250b2b4e3d7e55e657d52732a3b1d for more info - 
    note: at this moment $pubkeys is not implemented)