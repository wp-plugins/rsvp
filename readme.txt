=== RSVP Plugin ===
Contributors: mdedev
Tags: rsvp, reserve, wedding, guestlist
Requires at least: 2.5
Tested up to: 2.9.1
Stable tag: 0.5.1

Easy to use rsvp plugin originally created for weddings but could be used for other events.

== Description ==

This plugin was initially created for a wedding to make rsvp'ing easy as possible for guests. The main things we found lacking 
in existing plugins was:

* Couldn't relate attendees together so one person could easily rsvp for their whole family
* Required people to remember/know some special knowledge (a code, a zipcode, etc...)

The admin functionality allows you to do the following things:

* Specify the opening and close date to rsvp 
* Specify a custom greeting
* Specify the RSVP yes and no text
* Specify the kids meal verbiage
* Specify the vegetarian meal verbiage 
* Specify the text for the note question
* Enter in a custom thank you
* Import a guest list from an excel sheet (column #1 is the first name, column #2 is the last name)
* Export the guest list
* Add, edit and delete guests
* Associate guests with other guests

If there are any improvements or modifications you would like to see in the plugin please feel free to contact me at (mike AT mde DASH dev.com) and 
I will see if I can get them into the plugin for you.  

== Installation ==

1. Update the `rsvp` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add and/or import your attendees to the plugin and set the options you want
1. Create a blank page and add 'rsvp-pluginhere' on this page

== Frequently Asked Questions ==

= Why can't this plugin do X? =

Good question, maybe I didn't think about having this feature or didn't feel anyone would use it.  Contact me at mike AT mde DASH dev.com and 
I will see if I can get it added for you.  

== Screenshots ==

1. What a list of attendees looks like
1. The options page
1. The text you need to add for the rsvp front-end

== Changelog ==

= 0.5.0 =
* Initial release

= 0.5.1 =
* Fixed a bug reported by Andrew Moore in the import feature that would not allow most files from being uploaded, doh!
* Fixed a few other small warnings and gotchas (also reported by Andrew Moore)


== Upgrade Notice ==
To upgrade from 0.5.0 to 0.5.1 just re-upload all of the files and you should be good to go.  Really the only change was to wp-rsvp.php so uploading this changed file is all that is needed.  
