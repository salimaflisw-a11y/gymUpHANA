# Firebase Service Account Configuration

This directory contains the Firebase Admin credentials used by the GymUp backend.

## Instructions to set up Firebase Firestore:

1. Go to the [Firebase Console](https://console.firebase.google.com/).
2. Select or create your project (e.g. `GymUp`).
3. Under **Build**, click on **Firestore Database** and create a database (e.g. in `(default)` in your preferred region like `eur3` or `nam5`).
4. Click on the ⚙️ **Project Settings** (gear icon next to Project Overview) -> **Service accounts** tab.
5. Click **Generate new private key**, then confirm by clicking **Generate key**.
6. Save the downloaded `.json` file into this directory:
   `config/firebase/firebase_credentials.json`
7. Ensure `.gitignore` ignores `firebase_credentials.json` (already configured to protect your secrets).
8. Run the connection test and sync command:
   ```bash
   php bin/console firebase:sync --test
   ```

