# Firebase fleet management

The WordPress menu **VietRide Bikes** manages the Firestore bikes collection and uploads JPEG, PNG and WebP photos to the WordPress media library with the free setup. The public fleet reads a server-side cache. Firebase Console changes appear within five minutes.

## Connect a project

1. Create or select a Firebase project. Enable Cloud Firestore in Native mode using the (default) database and use the Spark plan. Cloud Storage is optional.
2. Create a dedicated service account with **Cloud Datastore User** permissions on the project. Add Storage Object Admin only if Cloud Storage is enabled later.
3. Download its JSON key into a private directory outside the WordPress public folder, such as ~/Local Sites/tuantran/private/firebase-service-account.json. Limit file permissions to the server account. Never commit or paste the key.
4. In WordPress → **VietRide Bikes** → **Firebase connection**, enter the Project ID and absolute JSON file path. Leave the Storage bucket empty for WordPress media uploads. Save the connection.
5. Select **Import missing bikes from current fleet** to copy the existing models. This imports the image references, while the original images continue to load from the theme. Firebase bikes with matching slugs are preserved.
6. Edit a bike, upload a photo and save. Uploaded photos appear in WordPress Media and their URLs are saved in Firestore. Existing media can be selected using the gallery and preview image pickers. Put the preferred image first in the gallery list and set the preview URL if needed.

The website keeps the current fleet until the first successful import or save. If Firebase later becomes unavailable, it serves the last successful fleet snapshot. Unchecking **Publish on the website** hides a model and its detail URL.

## Data and access

Each bike stores slug, name, brand, engine, type, description, price, deposit, thumb, images, docs, included and published. Price and deposit are optional display text, including currency and rental period. Existing slugs are read-only to preserve links.

Only WordPress administrators with manage_options can change the connection or edit bikes. Requests use WordPress nonces and service account OAuth tokens on the server. Firestore and Storage access use IAM. Public client read/write rules are unnecessary for this integration. Photo links are public, so upload only photos intended for the website.

Concurrent edits are protected with Firestore document versions. A stale edit fails instead of replacing newer changes. Uploading appends a gallery photo. Removing a gallery URL hides it from that model but retains the image in its media library or Storage bucket so existing links continue to work.

On production, enter a production-specific private credentials path in WordPress. The theme source contains no service account key.

References
- https://firebase.google.com/docs/firestore/use-rest-api
- https://firebase.google.com/docs/admin/setup
- https://firebase.google.com/docs/storage/admin/start


## VietRide project

Project ID is vietride-fleet-5d8e7. The default Firestore database uses asia-southeast1 (Singapore). WordPress Local is connected through its private service account and 18 bike records have been imported.

The Spark setup stores bike information in Firestore and new photos in the WordPress media library. The Storage bucket field is empty. Cloud Storage remains optional if Blaze is enabled later. Uploaded image files use WordPress hosting storage and bandwidth.

When moving the site from Local to production, migrate the WordPress media files and update stored media URLs in Firestore to the production domain.

## Rental rates, availability and photos

Each bike has optional price_daily, price_weekly and price_monthly fields. Enter the total for each period as whole VND amounts without thousands separators. An empty field displays Contact for rates. Legacy price notes are preserved and shown when all three rates are empty.

Availability is separate from visibility. Available allows a rental enquiry, Currently unavailable invites customers to enquire about future dates, and Check availability is the default for existing bikes whose actual availability has not been confirmed. Unpublishing still hides the entire listing.

The fleet table shows each bike's cover image, three rates, availability and visibility. In the editor, the photo grid previews saved gallery images. Set as main changes both the cover and the first gallery image. Move earlier/later reorders the gallery. Remove unlinks a photo from that bike and leaves the media file intact. Changes are stored when Save bike to Firebase is pressed.

Fuel / power can be Petrol or Electric. Electric bikes have an Electric fleet filter. The existing electric models are recognised from their legacy engine value even before they are saved with the fuel field. For electric models, the engine field can show motor power or Electric.
