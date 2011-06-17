=== FoxyPress ===
Contributors: webmovementllc
Tags: products, foxycart, cart, e-commerce, shopping cart, foxy, foxypress
Requires at least: 3.0
Tested up to: 3.1.3
Stable tag: trunk
Version: 0.2.9

FoxyPress allows you to easily create an inventory, view and track your orders, generate reports and much more...
== Description ==
FoxyPress allows you to easily add products to your WordPress pages using FoxyCart as your shopping cart solution. Manage inventories, set product options and organize transactions all within WordPress using a convenient WYSIWYG toolbar icon and administration panels. 

FoxyPress is developed and implemented soley by WebMovement, LLC. Additional FoxyPress fixes and features will be added based on forum users' requests. If you require custom functionality, please contact us directly at admin@foxy-press.com and we can build what you need.

[vimeo http://vimeo.com/22047284]

--Site credits--

- WebMovement, LLC - Plugin Development and conception

- Scott Hollencamp - Order Management inspiration

- Quesinberry - Gift Certificate inspiration and donation

- Uploadify - Multi-image support

== Changelog ==

= 0.2.9 =
* Added downloadable product support
* Added inventory ordering by category
* Added multi-ship support
* Added product detail base URL for sites with no URL-Rewriting
* Added Lightbox capability for single item template
* Added new shortcode detail (see notes)
* Added uninstall hook (cleans up tables, downloadable items and inventory images)
* Fixed product description line break issue
* Fixed product pricing format issue
* Fixed alot of random bugs brought to our attention by our users
* View the release notes <a href="http://www.foxy-press.com/archives/311" target="_blank">here</a>

= 0.2.8 =
* Added Non-Permalink site support
* View the release notes <a href="http://www.foxy-press.com/archives/248" target="_blank">here</a>

= 0.2.7 =
* Added Product Import/Export functionality
* Added Product Feed - compatible with Google Products
* Widget Support - MiniCart in Sidebar and JSON Cart Dropdown
* Tested with WP 3.1.2
* View the release notes <a href="http://www.foxy-press.com/archives/211" target="_blank">here</a>

= 0.2.6 =
* Modified reporting functionality to include dropdown for live/test/all transactions
* Modified reporting functionality to include price per total card transactions
* Added image thumbnails in the order management single item screen
* BugFix: New installs had some inventory problems. 
* View the release notes <a href="http://www.foxy-press.com/archives/204" target="_blank">here</a>

= 0.2.5 =
* Changed Single Product Template to use different CSS classes
* Added multiple image thumbnails to the Single Product Template
* View the release notes <a href="http://www.foxy-press.com/archives/168" target="_blank">here</a>

= 0.2.4 =
* Changed Single Product Template to use different CSS classes
* Added multiple image thumbnails to the Single Product Template

= 0.2.3 =
* BugFix: jQuery linking for qtip file.

= 0.2.2 =
* BugFix: FoxyCart Sync/Timeout issue
* Changed products in single mode to have full description

= 0.2.1 =
* BugFix: Fixed Item Deletion

= 0.2.0 =
* Added option to automatically include jQuery on user's site
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

Copy the folder to your WordPress 
'*/wp-content/plugins/*' folder.

1. Activate the '*FoxyPress*' plugin in your WordPress admin '*Plugins*'
1. Go to '*FoxyPress / Manage Settings*' in your WordPress admin.
1. Enter your FoxyCart domain.
1. Once activated, FoxyPress will automatically create a Product Detail page for your Category pages.

[vimeo http://vimeo.com/21743308]

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
1. Manage Settings Page - Add your FoxyCart subdomain here and view your API key.

2. Manage Inventory Page - Add and edit products to your store.

3. Add Inventory Page - Add your product and the details/images associated.

4. Edit Inventory Page - Edit your product's features, options and images.

4a. Edit Inventory Page - Custom options have been added to products.

5. Manage Option Groups Page - This page allows you to add sizes, colors, etc to your items.

6. Manage Categories Page - This page allows you to add categories for your items.  Remember to match your FoxyCart categories.

7. Order Management Page - This page allows you to keep track of your orders, as well as some customer information.

7a. Order Management Category - This page shows an example of all orders that are labeled for Processing.

8. Order Management Transaction Detail - This page shows information about the product purchased.

8a. Order Management Transaction Detail - Additional transaction details.

9. Status Management - This page allows you to edit different statuses for your transactions.

9a. Status Management Detail Page - A return email can be configured for users.

10. Product Listing Category Page - Here we are calling all items with a categoryid of 1, this is done through the popup.

10a. FoxyPress Modal popup - This page allows you to add single items to the page or product listings for a specific category.
 `[vimeo http://vimeo.com/22461104]`