# Patient Access (REDCap External Module)
Enabling and configuring this module for a REDCap project will override a chosen survey to instead display a dashboard of icons and links. Clicking on these links allows a user to open the link content in an area next to the dashboard for further exploration.

## Configuration
After enabling the module for a project, choose a survey to override by entering the survey hash in the "Survey Hash" field on the external module configuration page.
The survey hash is a 10 character, alphanumeric string that appears at the end of the URL when you visit the survey page:
https://redcap.vanderbilt.edu/redcap/surveys/?s=ABCDE12345
In the above example, "ABCDE12345" is the survey hash.

Then, clicking the '+' icon, add as many icons, icon labels, icon link urls, and icon link labels as you would like.
Finally, you can add footer links that will be shown at the bottom of the dashboard.