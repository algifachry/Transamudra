<?php
/**
 * GitHub Auto Uploader for Joomla Transmudra
 * Usage: php github_uploader.php
 */

class GitHubUploader {
    private $repoOwner = 'your-username';
    private $repoName = 'transamudra-joomla';
    private $accessToken = 'your-github-token';
    private $zipFile = 'transmudra_joomla.zip';

    public function uploadToGitHub() {
        echo "🚀 Starting GitHub Upload Process...\n";

        // Step 1: Create ZIP if not exists
        if (!file_exists($this->zipFile)) {
            echo "📦 Creating ZIP file...\n";
            $this->createZipFile();
        }

        // Step 2: Create GitHub release
        echo "📡 Uploading to GitHub...\n";
        $this->createGitHubRelease();

        echo "✅ Upload completed successfully!\n";
    }

    private function createZipFile() {
        // Include your existing ZIP creation script here
        require_once 'create_joomla_zip.php';
        $creator = new JoomlaZipCreator();
        $creator->createStructure();
        $creator->createZip($this->zipFile);
        $creator->cleanup();
    }

    private function createGitHubRelease() {
        $url = "https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases";

        $data = [
            'tag_name' => 'v1.0.0',
            'target_commitish' => 'main',
            'name' => 'Joomla Transmudra v1.0',
            'body' => $this->getReleaseDescription(),
            'draft' => false,
            'prerelease' => false
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $this->accessToken,
            'User-Agent: Joomla-Transmudra-Uploader',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 201) {
            $releaseData = json_decode($response, true);
            $this->uploadReleaseAsset($releaseData['upload_url']);
        } else {
            echo "❌ Failed to create release: " . $response . "\n";
        }

        curl_close($ch);
    }

    private function uploadReleaseAsset($uploadUrl) {
        $uploadUrl = str_replace('{?name,label}', '?name=' . $this->zipFile, $uploadUrl);

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $this->accessToken,
            'User-Agent: Joomla-Transmudra-Uploader',
            'Content-Type: application/zip'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($this->zipFile));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 201) {
            echo "✅ File uploaded successfully to GitHub!\n";
        } else {
            echo "❌ Failed to upload file: " . $response . "\n";
        }

        curl_close($ch);
    }

    private function getReleaseDescription() {
        return "# Joomla Package for PT Transamudra Usaha Sejahtera

## 📦 What's Included
- Complete Joomla 4.4.3 installation
- Custom Transamudra template with brand colors
- Infocom employee portal module
- Multilingual support (English & Indonesian)
- Ready for cPanel deployment

## 🚀 Installation
1. Download this ZIP file
2. Upload to your cPanel public_html
3. Extract all files
4. Run installation via browser
5. Follow the setup guide

## 🎨 Features
- **Brand Colors**: Blue (#003366), Red (#cc0000), White
- **Infocom System**: Internal employee portal with login requirement
- **Responsive Design**: Mobile-friendly template
- **Multilingual**: English and Indonesian support

## 📞 Support
Contact: it-support@transamudra.com

---
*PT Transamudra Usaha Sejahtera - Shipping & Logistics Excellence*";
    }
}

// Run the uploader
if (php_sapi_name() === 'cli') {
    $uploader = new GitHubUploader();
    $uploader->uploadToGitHub();
} else {
    echo "❌ This script must be run from command line.\n";
}
?>