=== FoxyPress ===
Contributors: webmovementllc
Tags: products, foxycart, cart, e-commerce, shopping cart, foxy, foxypress, press
Requires at least: 3.0
Tested up to: 3.1
Stable tag: trunk
Version: 0.2.0

This free plugin allows you to easily add products to your WordPress pages using FoxyCart as your shopping cart solution. Manage inventories, set product options and organize transactions all within WordPress using a convenient WYSIWYG toolbar icon. 
== Description ==
This free plugin allows you to easily add products to your WordPress pages using FoxyCart as your shopping cart solution. Manage inventories, set product options and organize transactions all within WordPress using a convenient WYSIWYG toolbar icon. 

FoxyPress is developed and implemented soley by WebMovement, LLC. Additional FoxyPress fixes and features will be added based on forum users’ requests. If you require custom functionality, please contact us directly at foxypress@webmovementllc.com.

--Site credits--
- WebMovement, LLC - Plugin Development and conception
- Scott Hollencamp - Order Management inspiration
- Uploadify - Multi-image support

== Changelog ==

= 0.2.0 =
* Added option to automatically include jQuery on user’s site
* Added Custom Inventory Item Options
* Added Multiple Option Groups for Products
* Added Custom Inventory Item Attributes
* New shortcode attribute to list items from a specific category. Includes paging options and items per row.
* New shortcode attribute to link items to an item detail page
* New shortcode attribute for an item detail page
* New shortcode attribute for an order detail module
* Created default item detail page on install for foxy products to land on
* Added search inventory option within the foxypress shortcode dialog window
* Added multiple image support for inventory items
* Added multiple category support for inventory items
* Implemented Uploadify for image uploading
* Added pagination on the inventory page
* Modified order management UI
* Added pagination in order management
* Added pagination to category management
* FoxyPress shortcode now uses inventory_id instead of code for single items

= 0.1.9 =
* Added: Custom fields, email address
* Added: Pagination
* Added: Showing hidden transactions

= 0.1.8 =
* General bug fixes and additional instructions.

= 0.1.7 =
* Added: Order Management - this management tab allows you to sync WordPress with your FoxyCart transactions/customers. This functionality allows you to add notes to orders, change their status, edit billing/shipping addresses, and add shipping/tracking information.
* Added: Status Management - this management tab allows you to add/edit/delete specific statuses that your transactions require.  You can choose to trigger email alerts and add tracking information.
* Changed: Inventory usage - the inventory is where you will keep track of all your products.  When you add an item from inventory to your page, it will only put the id of the product, then the item is pulled from the database when your page loads.  This allows you to change your product's name, price, etc and have it reflect on your already published pages.
* General bug fixes and additional instructions.

= 0.1.6 =
* Fixed: Default Image for inventory items.  Users must now select "use default image" if they want to apply the default image to their item
* Fixed: Listing of double inventory items.  Items will no longer list twice after editing.

= 0.1.5 =
* Fixed: Listing of double inventory items. Items will list only to the categories in which they were assigned.
* Fixed: JQuery Modal Window.  v0.1.4 contained modal window error. Modal window again loads.

= 0.1.4 =
* Fixed: Admin menu link errors. Links to inventory from Foxypress tab menu were invalid upon upgrade.  

= 0.1.3 =
* Added: Inventory features.  It is now possile to manage a simple inventory with add, edit, and delete functionality.  Inventory items may be inserted using the foxypress popup editor.
* Added: Disabled use of foxypress without foxycart store url
* Moved: Foxypress now uses its own tab on the admin menu.  Sub-tabs include settings, inventory management, and inventory category management 
 
= 0.1.2 =

* Fixed: JQuery include check updated.

= 0.1.1 =

* Fixed: Graphic changes

= 0.1.0 =

* Fixed: JQuery confliction.  A check is now made if JQuery is already included in your pages.

== Installation ==

1. Extract the zip file and drop the contents in the wp-content/plugins/ directory of your WordPress installation.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter your FoxyCart Store Subdomain URL found in FoxyCart's Admin section, then click save.

== Frequently Asked Questions ==

**Do I need a FoxyCart account to use this plugin?**

Yes, you do. Please signup for a FoxyCart account <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182" target="_blank">here</a>.

**Do I need to know HTML to use this plugin?**

No. That is the reason this plugin was made. You can learn the WP shortcode or use the WYSIWIG menu item to add your product through a short form.

**Why doesn't my add to cart button open the modal dialog?**

Make sure you don't have .foxycart.com at the end of your store subdomain.  You only need the subdomain in the textbox. Correct: websevenpointo Incorrect: websevenpointo.foxycart.com.

**Why doesn't my default image work?**

Make sure it is in the inventory_images folder.

**Where do I submit requests for new features or any comments I have?**

Go to the <a href="http://www.foxy-press.com/forum" target="_blank">FoxyPress Forum</a> to submit your question/comment/feature request.

== Upgrade Notice ==

This is a stable release.  Please view the changelog to see the bugs we fixed.

== Screenshots ==
1. This is a screenshot of the plugin component that you add products to your page/post through.

2. This is a screenshot of the plugin settings.
 `[vimeo http://www.vimeo.com/16263168]`