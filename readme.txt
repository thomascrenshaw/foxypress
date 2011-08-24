=== FoxyPress ===
Contributors: webmovementllc
Donate link: http://www.foxy-press.com/contact
Tags: foxycart, shopping cart, inventory, management, ecommerce, selling, subscription
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 3.0.1
FoxyPress is a one stop shopping cart and management tool for use with FoxyCart's hosted e-commerce solution.

== Description ==

FoxyPress is a complete shopping cart and inventory management tool for use with FoxyCart's e-commerce solution. Easily manage inventory, view and track orders, generate reports and much more.

FoxyPress was built to make the integration of FoxyCart and WordPress effortless and easy to understand. Our ever growing features and fantastic support make it easy to get your store up and running quickly!

Visit <a href="http://www.foxy-press.com" target="_blank">www.foxy-press.com</a> for documentation, examples, and webcasts to help you get started.

[vimeo http://vimeo.com/22047284]

= A few of the FoxyPress Features: =
* Sale pricing with optional date controls
* Product variations and pricing flexibility
* Product categories
* Product attribute options
* Inventory management
* Digital Products

FoxyPress is developed and implemented soley by WebMovement, LLC. Additional FoxyPress fixes and features will be added based on forum user requests. If you require custom functionality, please contact us directly at admin@foxy-press.com and we can build what you need.

--Site credits--

- WebMovement, LLC - Plugin Development and conception

- Scott Hollencamp - Order Management inspiration

- Quesinberry - Gift Certificate inspiration and donation

- Uploadify - Multi-image support

- Green Egg Media - Multi Datafeed inspiration

== Changelog ==

= 0.3.2 =
* Added the ability to edit product options, instead of deleting for every change
* Added ability to optionally monitor inventory on a product option level. Some limitations exist here, so please read the change log in full.
* Added an informational dashboard widget per forum request to display some quick stats about your cart.  Ability to enable/disable is found in the Manage Settings page.
* Added Lightbox as an option for photo gallery display.  This fixes a few conflicts that were occurring.
* Ability to have sale pricing on items and schedule the sale availability (start/end date).
* Start and end date availability for a product is now available, along with the ability to mark an item as inactive in general.
* Out of stock items and unavailable/inactive items now have customized messages that are available for editing on the settings page.
* Restructured the html for a few shortcodes.  You can see the updated documentation for CSS styling <a href="http://www.foxy-press.com/documentation/product-template-styling/" target="_blank">here</a>.
* View the release notes <a href="http://www.foxy-press.com/2011/08/foxypress-0-3-2-released/" target="_blank">here</a>

= 0.3.1 =
* Added support for multiple currencies
* Made the item description for inventory items a WYSIWYG editor (line break issue resolved)
* Added extra weight to the product options panel
* Allowed for price and weight reductions in the options panel, instead of additions only
* We had a table prefix issue, but changed code so you can have custom table prefixes
* Added shortcode modifications for a quantity box
* Added shortcode modifications for ability to show add to cart on list category mode
* View the release notes <a href="http://www.foxy-press.com/2011/07/foxypress-0-3-1-released/" target="_blank">here</a>

= 0.3.0 =
* Added support for the <a href="http://codex.wordpress.org/Installing_WordPress_With_Clean_Subversion_Repositories" target="_blank">clean svn repository method</a> by using plugins_url() instead of hardcoding the plugins folder
* Added inventory levels and alert settings
* Added multiple datafeed support
* Added ability to re-order photos per product
* Added minimum and maximum product amount available per product
* Fixed a few random bugs brought to our attention by our users
* View the release notes <a href="http://www.foxy-press.com/2011/07/foxypress-0-3-0-released/" target="_blank">here</a>

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
* View the release notes <a href="http://www.foxy-press.com/2011/06/foxypress-0-2-9-released/" target="_blank">here</a>

= 0.2.8 =
* Added Non-Permalink site support
* View the release notes <a href="http://www.foxy-press.com/2011/05/foxypress-0-2-8-released/" target="_blank">here</a>

= 0.2.7 =
* Added Product Import/Export functionality
* Added Product Feed - compatible with Google Products
* Widget Support - MiniCart in Sidebar and JSON Cart Dropdown
* Tested with WP 3.1.2
* View the release notes <a href="http://www.foxy-press.com/2011/05/foxypress-0-2-7-released/" target="_blank">here</a>

= 0.2.6 =
* Modified reporting functionality to include dropdown for live/test/all transactions
* Modified reporting functionality to include price per total card transactions
* Added image thumbnails in the order management single item screen
* BugFix: New installs had some inventory problems. 
* View the release notes <a href="http://www.foxy-press.com/2011/04/foxypress-0-2-6-released/" target="_blank">here</a>

= 0.2.5 =
* Changed Single Product Template to use different CSS classes
* Added multiple image thumbnails to the Single Product Template
* View the release notes <a href="http://www.foxy-press.com/2011/04/foxypress-0-2-5-released/" target="_blank">here</a>

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
2. Go to '*FoxyPress / Manage Settings*' in your WordPress admin.
3. Enter your FoxyCart domain.
4. Once activated, FoxyPress will automatically create a Product Detail page for your Category pages.
5. See installation documentation <a href="http://www.foxy-press.com/documentation/installation-instructions/" target="_blank">here</a>.

[vimeo http://vimeo.com/21743308]

== Frequently Asked Questions ==

**Do I need a FoxyCart account to use this plugin?**

Yes, you do. Please signup for a FoxyCart account <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=182" target="_blank">here</a>.

**Where do I submit requests for new features or any comments I have?**

Go to the <a href="http://www.foxy-press.com/forum" target="_blank">FoxyPress Forum</a> to submit your question/comment/feature request.

View a full list of questions on our <a href="http://www.foxy-press.com/frequently-asked-questions/" target="_blank">site</a>.

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