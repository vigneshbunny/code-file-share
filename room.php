<?php
// Convert room id to lower-case so that the URL is case insensitive.
$room_id = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
if (!$room_id) {
    die("Invalid room name.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="favicon.png" type="image/png">
  <title>DS Share - <?php echo htmlspecialchars($room_id); ?></title>
  
  <!-- Required CDN files -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.js"></script>
  <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
  <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
  
  <style>
    :root {
      --bg-primary: #000000;  /* Pure black */
      --bg-secondary: #000000;  /* Files section also pure black */
      --file-bg: #1e1e1e;      /* Lighter ash color for file items */
      --text-primary: #d4d4d4;
      --accent: #007acc;
      --border: #404040;
    }
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      height: 100vh;
      overflow: hidden;
    }
    .container {
      display: flex;
      height: 100vh;
    }
    .editor-section {
      flex: 0 0 70%;
      padding: 20px;
      max-width: 70%;
      overflow: auto;
    }
    .files-section {
      flex: 0 0 30%;
      max-width: 30%;
      padding: 20px;
      overflow-y: auto;
      background: var(--bg-secondary);
    }
    h1 {
      font-size: 1.2rem;
      margin-bottom: 20px;
      color: var(--text-primary);
      display: flex;
      align-items: center;
    }
    /* New link-icon copy button styles */
    .link-icon {
      display: inline-flex;
      align-items: center;
      cursor: pointer;
      transition: transform 0.2s;
      margin-left: 10px;
      font-size: 1.2rem;
      position: relative;
    }
    .link-icon:hover {
      transform: scale(1.2);
    }
    /* Tooltip is hidden by default, shown only on hover */
    .link-icon .tooltip {
      display: none;
      margin-left: 5px;
      font-size: 0.7rem;
      color: ash;
    }
    .link-icon:hover .tooltip {
      display: inline;
    }
    .CodeMirror {
      height: calc(100vh - 100px);
      font-family: 'JetBrains Mono', 'Fira Code', monospace;
      font-size: 14px;
      border-radius: 4px;
      background-color: #000000 !important;  /* Force black background */
    }
    /* Loader overlay for CodeMirror editor */
    #editorContainer {
      position: relative;
    }
    #editorLoader {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
      z-index: 10;
    }
    .upload-container {
      position: relative;
      margin-bottom: 20px;
      display: flex;
      flex-direction: column;
    }
    .upload-zone {
      border: 2px dashed var(--border);
      padding: 20px;
      text-align: center;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s;
      background: var(--file-bg);
    }
    .upload-zone.dragover {
      border-color: var(--accent);
      background: rgba(0, 122, 204, 0.1);
    }
    .upload-zone.file-selected {
      border-color: var(--accent);
    }
    .upload-zone.uploading {
      pointer-events: none;
      opacity: 0.7;
    }
    .upload-button {
      background: linear-gradient(135deg, #007acc, #005a8c);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 25px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      cursor: pointer;
    }
    .upload-button:hover {
      background: linear-gradient(135deg, #005a8c, #003a5c);
      transform: translateY(-2px);
      box-shadow: 0 6px 8px rgba(0,0,0,0.2);
    }
    #fileList {
      list-style: none;
    }
    .file-item {
      background: var(--file-bg);
      margin-bottom: 10px;
      padding: 10px;
      border-radius: 4px;
      position: relative;
      word-break: break-all;
      border: 1px solid var(--border);
      cursor: pointer;
      transition: transform 0.2s ease;
    }
    .file-item:hover {
      transform: translateY(-2px);
    }
    .file-preview {
      width: 100%;
      max-height: 150px;
      object-fit: cover;
      border-radius: 4px;
      margin-bottom: 8px;
      cursor: pointer;
    }
    .file-info {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 8px;
    }
    .file-name {
      flex-grow: 1;
    }
    .file-actions {
      display: flex;
      gap: 20px;
      opacity: 0;
      transition: opacity 0.5s;
    }
    .file-item:hover .file-actions {
      opacity: 1;
    }
    .file-actions button {
      background: none;
      border: none;
      cursor: pointer;
      padding: 5px;
    }
    .file-actions button img {
      transition: transform 0.5s ease;
    }
    .file-actions button:hover img {
      transform: scale(1.3);
    }
    .upload-progress {
      display: none;
      margin-top: 10px;
      height: 3px;
      background: var(--border);
      border-radius: 3px;
    }
    .upload-progress-bar {
      height: 100%;
      background: var(--accent);
      width: 0;
      transition: width 0.3s;
      border-radius: 3px;
    }
    .upload-buttons {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }
    .cancel-button {
      background: none;
      border: none;
      cursor: pointer;
      padding: 5px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .cancel-button svg {
      stroke: #dc3545;
      transition: transform 0.2s;
    }
    .cancel-button:hover svg {
      transform: scale(1.2);
      stroke: #a71d2a;
    }
    .file-list-container {
      max-height: calc(100vh - 220px);
      overflow-y: auto;
      position: relative;
    }
    /* Loader overlay for file list */
    #filesLoader {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
      z-index: 10;
    }
    .selected-files {
      margin-top: 10px;
      max-height: 150px;
      overflow-y: auto;
      padding: 10px;
      background: rgba(30, 30, 30, 0.5);
      border-radius: 4px;
      display: none;
    }
    .selected-file-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 5px;
      padding: 5px;
      background: rgba(60, 60, 60, 0.5);
      border-radius: 4px;
    }
    .file-size {
      color: #888;
      font-size: 0.85em;
      margin-left: 5px;
    }
    @media (max-width: 768px) {
      body {
        overflow: auto;
        height: auto;
      }
      .container {
        flex-direction: column;
        height: auto;
        overflow: visible;
      }
      .editor-section, .files-section {
        flex: 1;
        max-width: 100%;
        overflow: visible;
      }
      .CodeMirror {
        height: 50vh;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <section class="editor-section">
      <h1>
        DS-C codeshare: <?php echo htmlspecialchars($room_id); ?>
        <!-- New link-icon copy button -->
        <div class="link-icon" onclick="copyUrl()">
          🔗
          <span class="tooltip">COPY</span>
        </div>
      </h1>
      <!-- Editor container with loader overlay -->
      <div id="editorContainer">
        <textarea id="editor"></textarea>
        <div id="editorLoader">Loading content...</div>
      </div>
    </section>
    
    <section class="files-section">
      <div class="upload-container">
        <div id="uploadZone" class="upload-zone">
          <!-- Updated text to include any file type -->
          <div>Drag files here or click to upload</div>
          <input type="file" id="fileInput" hidden multiple>
        </div>
        <div id="selectedFiles" class="selected-files"></div>
        <div class="upload-buttons">
          <button id="uploadButton" class="upload-button" style="display:none;">Upload</button>
          <button id="cancelButton" class="cancel-button" style="display:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="upload-progress">
          <div class="upload-progress-bar"></div>
        </div>
      </div>
      <div class="file-list-container">
        <!-- Loader overlay for files -->
        <div id="filesLoader">Loading files...</div>
        <ul id="fileList"></ul>
      </div>
    </section>
  </div>

  <script>
    // Initialize CodeMirror
    const editor = CodeMirror.fromTextArea(document.getElementById('editor'), {
      lineNumbers: true,
      theme: 'dracula',
      mode: 'javascript',
      lineWrapping: true,
      tabSize: 2,
      autofocus: true
    });

    // Firebase Configuration
    const firebaseConfig = {
      apiKey: "",
      authDomain: "ds-share.firebaseapp.com",
      databaseURL: "https://.firebaseio.com",
      projectId: "ds-share",
      storageBucket: "ds-share.appspot.com",
      messagingSenderId: "xxxxxxxxxx",
      appId: "xxxxxxxxxxxxxxxxx"
    };

    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);
    const db = firebase.database();
    const roomRef = db.ref("rooms/<?php echo $room_id; ?>");
    const filesRef = roomRef.child("files");

    let lastSyncedContent = '';
    let syncTimeout;

    // Sync editor content with debounce.
    roomRef.child("content").on("value", (snapshot) => {
      if (snapshot.exists()) {
        const value = snapshot.val() || "";
        if (value !== editor.getValue()) {
          lastSyncedContent = value;
          editor.setValue(value);
          // Set cursor at line 1, column 0 to show the beginning.
          editor.setCursor(0, 0);
        }
      }
      // Hide the editor loader once content loads
      const editorLoader = document.getElementById('editorLoader');
      if (editorLoader) editorLoader.style.display = 'none';
    });

    editor.on("change", () => {
      clearTimeout(syncTimeout);
      syncTimeout = setTimeout(() => {
        const currentContent = editor.getValue();
        if (currentContent !== lastSyncedContent) {
          roomRef.child("content").set(currentContent);
          lastSyncedContent = currentContent;
        }
      }, 500);
    });

    // Copy URL functionality using the new link-icon.
    function copyUrl() {
      const room = "<?php echo $room_id; ?>";
      const url = 'https://dsshare.fwh.is/' + room;
      navigator.clipboard.writeText(url).then(() => {
        const tooltip = document.querySelector('.link-icon .tooltip');
        tooltip.textContent = 'COPIED!';
        setTimeout(() => {
          tooltip.textContent = 'COPY';
        }, 1500);
      });
    }

    // Multiple file upload handling
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const uploadButton = document.getElementById('uploadButton');
    const cancelButton = document.getElementById('cancelButton');
    const progressBar = document.querySelector('.upload-progress-bar');
    const progressContainer = document.querySelector('.upload-progress');
    const selectedFilesContainer = document.getElementById('selectedFiles');

    let isUploading = false;
    let selectedFiles = [];

    // Format bytes to human-readable size
    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(1024));
      return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      uploadZone.addEventListener(eventName, preventDefaults);
    });

    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
      uploadZone.addEventListener(eventName, () => {
        uploadZone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(eventName => {
      uploadZone.addEventListener(eventName, () => {
        uploadZone.classList.remove('dragover');
      });
    });

    uploadZone.addEventListener('click', () => fileInput.click());
    uploadZone.addEventListener('drop', handleDrop);
    fileInput.addEventListener('change', handleFileSelect);

    function handleDrop(e) {
      const files = Array.from(e.dataTransfer.files);
      if (files.length > 0) handleFiles(files);
    }

    function handleFileSelect(e) {
      const files = Array.from(e.target.files);
      if (files.length > 0) handleFiles(files);
    }

    function handleFiles(files) {
      if (isUploading) {
        alert('Please wait for current upload to complete');
        return;
      }
      const maxFiles = 10;
      if (files.length > maxFiles) {
        alert(`You can only upload ${maxFiles} files at a time`);
        files = files.slice(0, maxFiles);
      }
      selectedFiles = [];
      let totalSize = 0;
      let hasInvalidSize = false;
      
      files.forEach(file => {
        if (file.size > 50 * 1024 * 1024) {
          hasInvalidSize = true;
        } else {
          selectedFiles.push(file);
          totalSize += file.size;
        }
      });
      
      if (hasInvalidSize) {
        alert('Some files were skipped because they exceed the 50MB size limit');
      }
      if (selectedFiles.length === 0) {
        resetUploadZone();
        return;
      }
      uploadZone.classList.add('file-selected');
      selectedFilesContainer.style.display = 'block';
      selectedFilesContainer.innerHTML = selectedFiles.map(file => 
        `<div class="selected-file-item">
           <span>${file.name} <span class="file-size">(${formatFileSize(file.size)})</span></span>
           <button class="remove-file" data-name="${file.name}">✕</button>
         </div>`
      ).join('');
      
      document.querySelectorAll('.remove-file').forEach(button => {
        button.addEventListener('click', function() {
          const fileName = this.getAttribute('data-name');
          selectedFiles = selectedFiles.filter(file => file.name !== fileName);
          if (selectedFiles.length === 0) {
            resetUploadZone();
          } else {
            this.parentElement.remove();
          }
        });
      });
      
      const uploadText = document.querySelector('#uploadZone div');
      uploadText.innerHTML = `${selectedFiles.length} files selected (${formatFileSize(totalSize)})`;
      
      uploadButton.style.display = 'inline-block';
      cancelButton.style.display = 'inline-block';
      
      uploadButton.onclick = uploadFiles;
      cancelButton.onclick = resetUploadZone;
    }

    async function uploadFiles() {
      if (isUploading || selectedFiles.length === 0) return;
      isUploading = true;
      // Change upload button text and disable it during upload
      uploadButton.disabled = true;
      uploadButton.textContent = "Uploading...";
      uploadZone.classList.add('uploading');
      progressContainer.style.display = 'block';
      
      try {
        let completedUploads = 0;
        const totalFiles = selectedFiles.length;
        
        for (const file of selectedFiles) {
          let formData = new FormData();
          formData.append('file', file);
          formData.append('room_id', "<?php echo $room_id; ?>");
          
          const currentProgress = (completedUploads / totalFiles) * 100;
          progressBar.style.width = `${currentProgress}%`;
          
          try {
            const response = await fetch('upload.php', { 
              method: 'POST', 
              body: formData 
            });
            const data = await response.json();
            if (data.url) {
              filesRef.push({
                name: data.file,
                url: data.url,
                size: file.size,
                timestamp: Date.now()
              });
            }
            completedUploads++;
            progressBar.style.width = `${(completedUploads / totalFiles) * 100}%`;
          } catch (error) {
            console.error(`Upload failed for ${file.name}:`, error);
          }
        }
        progressBar.style.width = '100%';
      } catch (error) {
        console.error("Upload process failed:", error);
      } finally {
        setTimeout(() => {
          isUploading = false;
          // Reset upload button text and enable it after upload completes
          uploadButton.disabled = false;
          uploadButton.textContent = "Upload";
          resetUploadZone();
          uploadZone.classList.remove('uploading');
          progressContainer.style.display = 'none';
          progressBar.style.width = '0%';
        }, 500);
      }
    }

    function resetUploadZone() {
      fileInput.value = '';
      uploadButton.style.display = 'none';
      cancelButton.style.display = 'none';
      selectedFiles = [];
      uploadZone.classList.remove('file-selected');
      selectedFilesContainer.style.display = 'none';
      selectedFilesContainer.innerHTML = '';
      
      const uploadText = document.querySelector('#uploadZone div');
      uploadText.innerHTML = 'Drag files here or click to upload (max 10 files, 50MB each, any file type)';
    }

    filesRef.on("value", (snapshot) => {
      const fileList = document.getElementById('fileList');
      fileList.innerHTML = "";
      const files = [];
      snapshot.forEach((childSnapshot) => {
        files.push({
          key: childSnapshot.key,
          ...childSnapshot.val()
        });
      });
      files.sort((a, b) => b.timestamp - a.timestamp)
           .forEach(fileData => addFileToList(fileData));
      // Hide the files loader once files are loaded
      const filesLoader = document.getElementById('filesLoader');
      if (filesLoader) filesLoader.style.display = 'none';
    });

    function addFileToList(fileData) {
      const li = document.createElement("li");
      li.className = 'file-item';
      const preview = getFilePreview(fileData);
      const fileSize = fileData.size ? formatFileSize(fileData.size) : '';
      li.innerHTML = `
        <div onclick="previewFile('${fileData.url}', '${fileData.name}')">
          ${preview}
        </div>
        <div class="file-info">
          <div class="file-name">
            <span>${fileData.name}</span>
            <small style="display: block; margin-top: 5px; color: #888;">
              ${new Date(fileData.timestamp).toLocaleString()} ${fileSize ? '• ' + fileSize : ''}
            </small>
          </div>
          <div class="file-actions">
            <button onclick="event.stopPropagation(); directDownloadFile('${fileData.url}', '${fileData.name}')" title="Download">
              <img src="icons8-download-64.png" alt="Download" width="24" height="24">
            </button>
            <button onclick="event.stopPropagation(); deleteFile('${fileData.key}')" title="Delete">
              <img src="icons8-delete-30.png" alt="Delete" width="24" height="24">
            </button>
          </div>
        </div>
      `;
      fileList.appendChild(li);
    }

    function getFilePreview(fileData) {
      const ext = fileData.name.split('.').pop().toLowerCase();
      const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
      const videoExts = ['mp4', 'avi', 'mov'];
      const docExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
      const officeIcons = {
        'pdf': '📄',
        'doc': '📝',
        'docx': '📝',
        'ppt': '📊',
        'pptx': '📊',
        'xls': '📈',
        'xlsx': '📈'
      };

      if (imageExts.includes(ext)) {
        return `<img src="${fileData.url}" class="file-preview" alt="${fileData.name}" />`;
      } else if (videoExts.includes(ext)) {
        return `<video src="${fileData.url}" class="file-preview" controls style="background: #000;"></video>`;
      } else if (docExts.includes(ext)) {
        return `
          <div class="file-preview" style="
              background-color: #f0f0f0; 
              display: flex; 
              align-items: center; 
              justify-content: center; 
              color: #333; 
              font-size: 48px;
              height: 150px;
          ">
              ${officeIcons[ext] || '📄'}
          </div>
        `;
      }
      // For any other file type, show a generic preview
      return `
        <div class="file-preview" style="
            background-color: #f0f0f0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #333; 
            font-weight: bold; 
            text-transform: uppercase;
            height: 150px;
        ">
            ${ext} File
        </div>
      `;
    }

    function deleteFile(key) {
      if (confirm('Delete this file?')) {
        filesRef.child(key).remove();
      }
    }

    function directDownloadFile(url, fileName) {
      const link = document.createElement('a');
      link.href = url;
      link.download = fileName;
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      setTimeout(() => {
        document.body.removeChild(link);
      }, 100);
    }

    function previewFile(url, fileName) {
      window.open(url, '_blank');
    }
  </script>
  <script>
    // Disable Right-Click and specific key combinations for security
    document.addEventListener("contextmenu", (event) => event.preventDefault());
    document.addEventListener("keydown", (event) => {
      if (
        event.ctrlKey && (event.key === "u" || event.key === "U" ||
        event.key === "i" || event.key === "I" ||
        event.key === "j" || event.key === "J")
      ) {
        event.preventDefault();
      }
      if (event.key === "F12") {
        event.preventDefault();
      }
    });
  </script>
</body>
</html>
