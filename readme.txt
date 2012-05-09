=== FoxyPress ===
Contributors: webmovementllc
Donate link: http://www.foxy-press.com/support/
Tags: foxycart, shopping cart, inventory, management, ecommerce, selling, subscription
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 3.0.1
FoxyPress is a FREE shopping cart and product management tool that integrates with FoxyCart's e-commerce solution.

== Description ==

FoxyPress is a FREE shopping cart and product management tool that integrates with FoxyCart's e-commerce solution to help you get your store up and running quickly and efficiently. Take an in-depth look at some of our major features below, aimed to help alleviate the stress of building your site, so you can focus on making your products shine.

The FoxyPress Team bases their features on their customers' needs, so if you don't see a tool you need, they will build it for you. For more information or to submit a request, visit <a href="http://www.foxy-press.com" target="_blank">Foxy-Press.com</a>.

[vimeo http://vimeo.com/22047284]

= A few of the FoxyPress Features: =
* Sale pricing with optional date controls
* Product variations and pricing flexibility
* Product categories
* Product attribute options
* Inventory management
* Digital Products
* Demo Store - <a href="http://demo.foxy-press.com" target="_blank">http://demo.foxy-press.com</a>
* Knowledgebase/Tutorials - <a href="http://forum.foxy-press.com" target="_blank">http://forum.foxy-press.com</a>

FoxyPress is developed and implemented soley by WebMovement, LLC. Additional FoxyPress fixes and features will be added based on forum user requests. If you require custom functionality, please contact us directly at admin@foxy-press.com and we can build what you need.

--Site credits--
- WebMovement, LLC - Plugin Development and conception
- Scott Hollencamp - Order Management inspiration
- Quesinberry - donation
- Uploadify - Multi-image support
- Green Egg Media - Multi Datafeed inspiration
- JasonHunterDesign - Affiliate Management inspiration/donation
- Consolibyte API - Utique Shop
- Trinity Hockey Co - Affiliate Management Upgrades
- Adam Morrissey (Delgado Protocol) - Affiliate Tier addition - affiliates can gain referral bonuses
- WeThePrinters - User Portal addition - Order History
- Adam Morrissey (Delgado Protocol) - User Portal addition - Affiliate Stats
- MamaDoo Kids - Status Management enhancements

== Changelog ==

= 0.4.1.1 =
* Feature: Modified status change in order management details to be an AJAX call rather than full postback.
* Feature: Added breadcrumb area above transaction details for easy navigation back to previous status.
* Feature: New KnowledgeBase articles available <a href="http://forum.foxy-press.com/" target="_blank">here</a>.

= 0.4.1 =
* Feature: Added the ability to use placeholders in Status emails. 
* Feature: Added functionality to the API to allow querying of affiliate statistics
* Bugfix: Mail function is now pulling WordPress admin email and blog name for all FoxyPress generated emails.
* Release notes available <a href="http://www.foxy-press.com/blog/2012/04/13/foxypress-0-4-1/" target="_blank">here</a> 

= 0.4.0.2 =
* Feature: Added additional sortable columns in the affiliate management datagrids. First Name, Last Name, total $ due, and total commission made.
* Feature: Set the number of affiliates in grid to 20.

= 0.4.0.1 =
* Bugfix: Fixed some bugs in the datafeed.

= 0.4.0 =
* Feature: We've added some support for our theme panel. You will want to upgrade to the latest version in order to use themes properly.
* Release notes available <a href="http://www.foxy-press.com/blog/2012/03/12/foxypress-0-4-0/" target="_blank">here</a> 

= 0.3.8.1 =
* Bugfix: The datafeed was corrected so that the new feature to push orders automatically without syncing now works.
* Bugfix: foxypress_Mail was not sending a subject unless SMTP was used. This has been corrected.
* Update: New API method foxypress_GetUserTransactions was modified to allow for $type to be left blank so all orders can come through.
* Update: FoxyPress User Portal Sample file updated. Download the example file <a href="http://www.foxy-press.com/wp-content/uploads/2012/02/foxypress-user-portal.php.zip" target="_blank">here</a>
* Release notes available <a href="http://www.foxy-press.com/blog/2012/02/16/foxypress-0-3-8-1/" target="_blank">here</a> 

= 0.3.8 =
* Feature: We added the ability for affiliates to receive refferal payouts in dollar amounts or percentages per transaction
* Feature: We added a user portal that contains your order history. Read release notes.
* Feature: New API Methods available for User Transactions.  Documentation will be up shortly <a href="http://www.foxy-press.com/getting-started/helper-functions-api/" target="_blank">here</a>.
* Update: Added some documentation to the Manage Category page
* Release notes available <a href="http://www.foxy-press.com/blog/2012/02/13/foxypress-0-3-8/" target="_blank">here</a> 
* User Request/Voting available <a href="https://foxypress.uservoice.com/forums/149794-main-request-forum" target="_blank">here</a> 

= 0.3.7.2 =
* Feature: We added the ability to have the hour and minute for sale and availability start/end date. 
* Update: Updated the GetProducts function parameter and query to be more logical. 
* Update: Updated the jQuery UI version and smoothness css file. 

= 0.3.7.1 =
* Bugfix: Fixed a quantity logic bug. 

= 0.3.7 =
* Feature: Ability to send out a coupon code either static or random with a product purchase
* Feature: Ability to trigger an email from Manage Emails area when a product is purchased. Custom fields are not allowed, only those in the legend.
* Feature: New Report to view orders containing a coupon by product code - useful for tracking who you sent coupon codes to.
* Bugfix: Fix for negative quantities received from Consolibyte Connector
* Release notes available <a href="http://www.foxy-press.com/blog/2012/1/12/foxypress-0-3-7" target="_blank">here</a> 

= 0.3.6.3 =
* Feature: Affiliate management now lets you set a discount amount that your customers will see when viewing products through affiliate link.
* Feature: Added product options into packing slip, when available.
* Feature: Added shipping options into packing slip.
* Bugfix: Packing slips weren't using the shipping name when available.
* Release notes available <a href="http://www.foxy-press.com/blog/2011/12/19/foxypress-0-3-6-3" target="_blank">here</a> 

= 0.3.6.2 =
* Bugfix: Affiliate management query fix for multi-sites.

= 0.3.6.1 =
* Feature: If you are using Consolibyte's QuickBooks integration, you can now send QuickBooks inventory quantities back up to FoxyPress
* Feature: Affiliate users can now have avatars - courtesy of Uploadify
* Bugfix: Multi-site users could have experienced some warnings about our blog install method based on their PHP settings.  This is resolved.
* Upgrade notes available <a href="http://www.foxy-press.com/blog/2011/12/09/foxypress-0-3-6-1" target="_blank">here</a> 

= 0.3.6 =
* Feature: Inventory Import from CSV file now supports images.
* Feature: Order Management fields added: credit card type, rma number
* Feature: Email Template Management available - read details <a href="http://www.foxy-press.com/getting-started/email-management" target="_blank">here</a>
* Feature: Affiliate Management is now multi-site friendly.  Read details for upgrade <a href="http://www.foxy-press.com/blog/2011/12/07/foxypress-0-3-6" target="_blank">here</a> 
* Bugfix: Sale Price was saving 0.00 on default. Should save blank if not provided now.
* Bugfix: Order management detail screen was using customer name at all times, instead of possibly different ship to name.
* Update: Keep Products on uninstall is now checked by default
* Update: Small UI enhancements

= 0.3.5.3 =
* Feature: Template caching update to include text and html email receipt subjects.
* Feature: Affiliate commission type available.  Pay Affiliates by a dollar amount or percentage rate per transaction.

= 0.3.5.2 =
* Feature: Support for FoxyCart Version 0.7.2 to accommodate FoxyCart's latest public beta.
* Feature: Template Caching (must be using store version 0.7.2)
* Feature: Affiliate user now receives an email upon marking an order paid.
* Feature: FoxyCart 0.7.2 Subscription DataFeed implemented

= 0.3.5.1 =
* Update: Minor styling changes to the affiliate management pages
* Bugfix: Some installs/servers were having problems with implode() function.

= 0.3.5 =
* Added ability to set users as affiliates, assign commission, and generate clicks/views.
* Added ability for new users to request affiliate status
* API Mod: foxyPress_GetProducts has been extended. View notes here.
* Bugfix: Bulk packing slips now use the custom message entered in the Settings page.
* Bugfix: Added a WP-Config definition for if you're using a sub folder install.
* View the release notes <a href="http://www.foxy-press.com/blog/2011/11/02/foxypress-0-3-5-released/" target="_blank">here</a>

= 0.3.4 =
* Per user request, we've added a packing slip wizard to be used for partial orders.  Handy for returns or back-ordered items.
* Product grid can now be sorted by additional product attributes
* HMAC Form Code added - no more form tampering : Courtesy of Brett from FoxyCart
* Additional setting added to Manage Settings Page  to control if our default FoxyPress stylesheet is included.  
* Per user request, SMTP Mail settings added to Manage Settings Page - useful for overcoming server relay trouble.
* Added Quantity available in the product grid - hide from screen options if you do not need this.
* Bugfix : New products now have the default category applied.
* Bugfix: Showing correct price/sale price on manage products page now.
* View the release notes <a href="http://www.foxy-press.com/blog/2011/10/19/foxypress-0-3-4-released/" target="_blank">here</a>

= 0.3.3 =
* WordPress Multi-site support - very important to read release notes on this.
* Single Sign On Support for FoxyCart 
* Products are now custom post types - new look and feel
* Inventory Management - utilizes WordPress grid for sorting/searching/quick and bulk edit
* Ability to upload a featured product image
* Ability to upload an image for a specific category
* Added new option for image gallery management - can have lightbox, colorbox, or change in placeholder
* We now have an API available so custom page templates can be created, as well as other functionality.  Check documentation in the release notes.
* New shortcode for a search module. [FoxyPress mode='search']FoxyPress[/FoxyPress], as well as API method.
* Subscription support to view/manage them, as well as create them as new products.
* Order management "uncategorized" status is now "processing" and is editable
* Added a new report for viewing order totals by product code
* Status Management Email body is now a WYSIWYG editor
* Default Category provided with FoxyPress is "Default" to match your FoxyCart store
* FoxyPress Wizard is now available for first time users to help you get started easier.
* Added documentation for API and entire functionality of FoxyPress
* Added new FoxyPress templates throughout Demo Store for slider and daily deal functionality.
* Added conversion tool to transfer all inventory into native WordPress tables.
* View the release notes <a href="http://www.foxy-press.com/blog/2011/10/foxypress-0-3-3-released/" target="_blank">here</a>

= 0.3.2 =
* Added the ability to edit product options, instead of deleting for every change
* Added ability to optionally monitor inventory on a product option level. Some limitations exist here, so please read the change log in full.
* Added an informational dashboard widget per forum request to display some quick stats about your cart.  Ability to enable/disable is found in the Manage Settings page.
* Added Lightbox as an option for photo gallery display.  This fixes a few conflicts that were occurring.
* Ability to have sale pricing on items and schedule the sale availability (start/end date).
* Start and end date availability for a product is now available, along with the ability to mark an item as inactive in general.
* Out of stock items and unavailable/inactive items now have customized messages that are available for editing on the settings page.
* Restructured the html for a few shortcodes.  You can see the updated documentation for CSS styling <a href="http://www.foxy-press.com/documentation/product-template-styling/" target="_blank">here</a>.
* View the release notes <a href="http://www.foxy-press.com/blog/2011/08/foxypress-0-3-2-released/" target="_blank">here</a>

= 0.3.1 =
* Added support for multiple currencies
* Made the item description for inventory items a WYSIWYG editor (line break issue resolved)
* Added extra weight to the product options panel
* Allowed for price and weight reductions in the options panel, instead of additions only
* We had a table prefix issue, but changed code so you can have custom table prefixes
* Added shortcode modifications for a quantity box
* Added shortcode modifications for ability to show add to cart on list category mode
* View the release notes <a href="http://www.foxy-press.com/blog/2011/07/foxypress-0-3-1-released/" target="_blank">here</a>

= 0.3.0 =
* Added support for the <a href="http://codex.wordpress.org/Installing_WordPress_With_Clean_Subversion_Repositories" target="_blank">clean svn repository method</a> by using plugins_url() instead of hardcoding the plugins folder
* Added inventory levels and alert settings
* Added multiple datafeed support
* Added ability to re-order photos per product
* Added minimum and maximum product amount available per product
* Fixed a few random bugs brought to our attention by our users
* View the release notes <a href="http://www.foxy-press.com/blog/2011/07/foxypress-0-3-0-released/" target="_blank">here</a>

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
* View the release notes <a href="http://www.foxy-press.com/blog/2011/06/foxypress-0-2-9-released/" target="_blank">here</a>

= 0.2.8 =
* Added Non-Permalink site support
* View the release notes <a href="http://www.foxy-press.com/blog/2011/05/foxypress-0-2-8-released/" target="_blank">here</a>

= 0.2.7 =
* Added Product Import/Export functionality
* Added Product Feed - compatible with Google Products
* Widget Support - MiniCart in Sidebar and JSON Cart Dropdown
* Tested with WP 3.1.2
* View the release notes <a href="http://www.foxy-press.com/blog/2011/05/foxypress-0-2-7-released/" target="_blank">here</a>

= 0.2.6 =
* Modified reporting functionality to include dropdown for live/test/all transactions
* Modified reporting functionality to include price per total card transactions
* Added image thumbnails in the order management single item screen
* BugFix: New installs had some inventory problems. 
* View the release notes <a href="http://www.foxy-press.com/blog/2011/04/foxypress-0-2-6-released/" target="_blank">here</a>

= 0.2.5 =
* Changed Single Product Template to use different CSS classes
* Added multiple image thumbnails to the Single Product Template
* View the release notes <a href="http://www.foxy-press.com/blog/2011/04/foxypress-0-2-5-released/" target="_blank">here</a>

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
1. FoxyPress Settings Wizard - Use the wizard to get you started.

1a. FoxyPress Settings - Set your API key, store domain, and much more!

2. Manage Products Page - Add and edit products to your store.

3. Add Product Page - Add your product and the details/images associated.

4. Manage Option Groups Page - This page allows you to add sizes, colors, etc to your items.

5. Manage Categories Page - This page allows you to add categories for your items.  Remember to match your FoxyCart categories.

6. Order Management Page - This page allows you to keep track of your orders, as well as some customer information.

6a. Order Management Category - This page shows an example of all orders that are labeled for Processing.

6b. Order Management Transaction Detail - This page shows information about the product purchased.

7. Status Management - This page allows you to edit different statuses for your transactions.

7a. Status Management Detail Page - A return email can be configured for users.

8. Reporting - We have a few reports available to keep track of your sales.

8a. Reporting - Another available report.

9. Subscriptions - View and edit your existing subscriptions

10. Import/Export - Easily take your inventory from site to site by importing/exporting it.

11. Affiliate Management - Easily manage approved/pending affiliates that are in your store.

11a. Affiliate Management - View affiliate stats for a specific user, pay orders, and view their history.

12. Affiliate Signup - Allow Users that sign up for your site to apply for affiliate status.