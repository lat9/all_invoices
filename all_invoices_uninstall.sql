#
# Removes the admin page key from the database for the All Invoices plugin (version 2.0.0 and later)
# lat9@vinosdefrutastropicales.com
#
DELETE FROM admin_pages WHERE page_key='reportsAllInvoices';