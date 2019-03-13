# Section 1 - Description
## 1.1 - Purpose
 
 
## 1.2 - Usage and Scope


## 1.3 - Definitions, Acronyms and Abbreviations
Term | Description
---- | -----------


## 1.4 - References


***  
***    
# Section 2 - NameHere  
## 2.1 - Purpose
  
***  
#### 2.2.1 - 
##### 2.2.1.1 - Purpose

##### 2.2.1.2 - Usage and Scope
File | Location | Description
-----| -------- | -----------

##### 2.2.1.3 - Constraints
##### 2.2.1.4 - Deployment
##### 2.2.1.5 - Issues and Risks

# Introduction
This readme assumes you have a Joomla 1.5.x and Community Builder 1.2.x installation already setup. It also assumes you're familiar with editing PHP files. If you're shakey on the latter this process might not be best for you.

This ZIP file contains all the files necessary to setup Community Builder to use a secondary confirmation process to upgrade a user's access level based on a value stored in a CB data field. I personally used it to confirm Purchase ID's for a book.

The plugin uses an email message which is sent to a third party, presumably an Administrator in Joomla. This user then verifies the value submitted and clicks one of the supplied links to confirm or deny the access upgrade.

Here's the scenario I wanted to solve:
- User clicks Login/Register Here button for Community Builder
Registration form displayed with extra NxtLvlWatchField
- User fills out form including NxtLvlWatchField value
- User submits form
- Normal CB User email confirmation sent
- User clicks email confirmation link
- CB onAfterUserConfirm Event triggers NxtLvlConfirm plugin to email "NxtLvlEmailToUser"(Admin typically) to confirm NxtLvlWatchField value, if entered
- "NxtLvlEmailToUser" clicks confirmation link which triggers NxtLvlConfirm which upgrades User Access level to NxtLvlOnConfirmedGroup
- User able to view/download NxtLvlOnConfirmedGroup content

# Details
The process to get this plugin up and running is:
1. Download and install the plugin via the CB Plugin Manager
2. Add the appropriate fields via the CB Field Manager, one must be named nxtlvlactivation, the other is configurable in the next step
3. Once the install completes successfully, click the Next Level Confirmation plugin link in the CB Plugin Manager list and configure/enable.
4. Hack the comprofiler.php core file for CB using the corehack files from svn as a guide.

## Files and Descriptions
- [nxtlvlconfirm.xml](devel/nxtlvlconfirm.xml) - Next Level Confirmation CB plugin installation settings file
- [nxtlvlconfirm.php](devel/nxtlvlconfirm.php) - Next Level Confirmation CB plugin
- [corehack/comprofiler.php](corehack/comprofiler.php) - CB Core hack example
- [corehack/nxtlvlMessages.txt](corehack/nxtlvlMessages.txt) - Example email settings for the Next Level Confirmation plugin

## To install the CB plugin
To install the Next Level Confirmation plugin in Community Builder do the following:
1. Download the latest plugin from the downloads page of this project.
2. Login to the Joomla Administration area for your site and access the Plugin Manager for Community Builder. Click the Install Plugin link, click Browse and select the archive for plugin then click Upload File & Install.

## To create the CB Fields
Next you'll need to create a couple of extra fields to use during the confirmation process:
1. Go to the CB Field Manager.
2. Click the New Field button
3. I use the following as an example for the field settings:
  - Type - Text Field
  - Tab - Contact Info
  - Name - bookorderid (Note CB will automatically prepend cb to the name)
  - Title - Book Order ID
  - Description - This is the Order ID on your invoice
  - Required - No
  - Show on Profile - Yes: on 1 line
  - Display Field Title - Yes
  - Searchable - No
  - Read Only - No
  - Show at Registration - Yes
  - Published - Yes
  - Size - 25
  - Max - 40
  - Authorized Input - Custom Perl regular expression
  - Regular Expression - /^[0-9A-z-]{8,35}$/
  - Error message - The Order ID does not appear to be valid. Should be a single set of characters and numbers at least 8 digits in length.
4. Save the new Field
5. Click the New Field button again
6. I use the following as an example for the field settings:
  - Type - Text Field
  - Tab - Contact Info
  - Name - nxtlvlactivation (Note CB will automatically prepend cbto the name)
  - Title - Next Level Activation Code
  - Required - No
  - Show on Profile - No
  - Display Field Title - No
  - Searchable - No
  - Read Only - No
  - Show at Registration - No
  - Published - Yes
  - Size - 50
7. Save the new Field

## To configure the CB plugin
Next we'll need to configure the settings for the plugin to match the fields we created:
1. Goto the CB Plugin Manager
2. Click the Next Level Confirmation plugin link
3. I use the following as an example for the field settings:
  - NxtLevel Confirmation Enabled - Yes
  - NxtLevel Field Name - cb_bookorderid
  - NxtLevel Group Name - Author (19)
  - Email From Id - Super Administrator (62)
  - Email To Id - Super Administrator (62)
  - All the rest can be left with the defaults. Just be aware if you modify these that the field placeholders need to be there.

## To Hack the Core comprofiler.php
Next we have to hack a core file for CB. Backup Backup Backup. Oh yeah Backup.
1. Copy the CB core comprofiler.php in siteroot/components/com_profiler and store it as a backup.
2. Locate and download the comprofiler.php from this projects source
3. Open a copy of the core comprofiler.php file and add the sections from this projects comprofiler.php that exist between comment blocks /NxtLvl Mod/. There are two sections of code to add. Lines 145 to 155 and 2400 to 2568.
4. Save/Upload the modified comprofiler.php file to your site.
5. Test

## Test the Confirmation Process
1. Create a test account using the normal CB login process. Confirm the email address by clicking the CB supplied link.
2. Once confirmed, the test user should receive a confirmation message. The administrative contact you set, should also get a message asking for confirmation of the secondary identifier. Click the confirm link in that email and the test user should be upgraded to the group you specified in the settings.
