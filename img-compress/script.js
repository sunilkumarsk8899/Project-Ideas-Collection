const uploadBox = document.querySelector(".upload-box"),
// previewImg = uploadBox.querySelector("img"),
// previewImg = document.createElement('img'),
previewImgs = document.querySelector('.img-cards');
fileInput = uploadBox.querySelector("input"),
widthInput = document.querySelector(".width input"),
heightInput = document.querySelector(".height input"),
ratioInput = document.querySelector(".ratio input"),
qualityInput = document.querySelector(".quality input"),
downloadBtn = document.querySelector(".download-btn");





  /** show dropdown option if quality reduce checkbox is checked */
  document.addEventListener('DOMContentLoaded', function () {
    let qualityDiv = document.querySelector('.quality_size');

    // Check if the 'quality_size' div exists before running the code
    if (qualityDiv && qualityInput) {
        qualityDiv.style.display = 'none'; // Default hidden
        qualityInput.addEventListener('click', function () {
            qualityDiv.style.display = qualityInput.checked ? 'block' : 'none';
        });
    } else {
        console.log('Element with class "quality_size" or the input is missing.');
    }
});






    // Handle drag over
uploadBox.addEventListener('dragover', (event) => {
    event.preventDefault();
    uploadBox.classList.add('dragover');
});

// Handle drag leave
uploadBox.addEventListener('dragleave', () => {
    uploadBox.classList.remove('dragover');
});

// Handle drop
uploadBox.addEventListener('drop', (event) => {
    event.preventDefault();
    uploadBox.classList.remove('dragover');

    const files = event.dataTransfer.files;
    handleFiles(files);
});


function handleFiles(files){
    previewImgs.innerHTML = '';

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileType = file.type.split('/')[1]; // Extract file type (e.g., png, jpeg)
        const fileName = file.name; // Get original file name

        const imgContainer = document.createElement('div'); // Create a container for the image and button
        const img = document.createElement('img'); // Create a new img element for each file
        const deleteButton = document.createElement('button'); // Create a delete button

        img.src = URL.createObjectURL(file); // Pass selected file URL to img src
        img.classList.add('responsive-img');

        deleteButton.textContent = 'X'; // Set button text
        deleteButton.classList.add('delete-button'); // Add a class for styling if needed

        // Append img and button to the container
        imgContainer.appendChild(img);
        imgContainer.appendChild(deleteButton);
        previewImgs.appendChild(imgContainer); // Append the container to previewImgs

        img.addEventListener("load", () => { // Once img is loaded
            widthInput.value = img.naturalWidth;
            heightInput.value = img.naturalHeight;
            ogImageRatio = img.naturalWidth / img.naturalHeight;
            document.querySelector(".wrapper").classList.add("active");

            // Add metadata to img element for download
            img.dataset.fileName = fileName;
            img.dataset.fileType = fileType;
        });

        // Add event listener for delete button
        deleteButton.addEventListener('click', () => {
            imgContainer.remove(); // Remove the image container from the preview
            // Optionally, reset width and height inputs if no images remain
            if (previewImgs.children.length === 0) {
                widthInput.value = '';
                heightInput.value = '';
                document.querySelector(".wrapper").classList.remove("active");
            }
        });
    }
}



  

let ogImageRatio;
const loadFile = (e) => {

    const files = e.target.files; // Getting all user-selected files
    if (!files.length) return; // Return if user hasn't selected any files

    // Clear previous images
    previewImgs.innerHTML = '';

    // handleFiles(files);

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileType = file.type.split('/')[1]; // Extract file type (e.g., png, jpeg)
        const fileName = file.name; // Get original file name

        const imgContainer = document.createElement('div'); // Create a container for the image and button
        const img = document.createElement('img'); // Create a new img element for each file
        const deleteButton = document.createElement('button'); // Create a delete button

        img.src = URL.createObjectURL(file); // Pass selected file URL to img src
        img.classList.add('responsive-img');

        deleteButton.textContent = 'X'; // Set button text
        deleteButton.classList.add('delete-button'); // Add a class for styling if needed

        // Append img and button to the container
        imgContainer.appendChild(img);
        imgContainer.appendChild(deleteButton);
        previewImgs.appendChild(imgContainer); // Append the container to previewImgs

        img.addEventListener("load", () => { // Once img is loaded
            widthInput.value = img.naturalWidth;
            heightInput.value = img.naturalHeight;
            ogImageRatio = img.naturalWidth / img.naturalHeight;
            document.querySelector(".wrapper").classList.add("active");

            // Add metadata to img element for download
            img.dataset.fileName = fileName;
            img.dataset.fileType = fileType;
        });

        // Add event listener for delete button
        deleteButton.addEventListener('click', () => {
            imgContainer.remove(); // Remove the image container from the preview
            // Optionally, reset width and height inputs if no images remain
            if (previewImgs.children.length === 0) {
                widthInput.value = '';
                heightInput.value = '';
                document.querySelector(".wrapper").classList.remove("active");
            }
        });
    }
};


widthInput.addEventListener("keyup", () => {
    // getting height according to the ratio checkbox status
    const height = ratioInput.checked ? widthInput.value / ogImageRatio : heightInput.value;
    heightInput.value = Math.floor(height);
});

heightInput.addEventListener("keyup", () => {
    // getting width according to the ratio checkbox status
    const width = ratioInput.checked ? heightInput.value * ogImageRatio : widthInput.value;
    widthInput.value = Math.floor(width);
});

const resizeAndDownloadAsZip = async () => {
    const images = previewImgs.querySelectorAll('img'); // Select all img elements
    if (!images.length) {
        console.error("No images to download.");
        return;
    }

    // Set image quality to 90%
    const qualityDropdown = document.getElementById('quality_size');
    const imgQuality = qualityDropdown && qualityDropdown.value ? parseFloat(qualityDropdown.value) : 1.0;
    
    console.log("Selected Quality (should be 0.9 for 90%):", imgQuality);
    

    const zip = new JSZip(); // Initialize a new JSZip instance
    const progressIndicator = document.createElement('div');
    progressIndicator.id = 'progressIndicator';
    progressIndicator.style.position = 'fixed';
    progressIndicator.style.top = '20px';
    progressIndicator.style.left = '20px';
    progressIndicator.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    progressIndicator.style.color = 'white';
    progressIndicator.style.padding = '10px';
    progressIndicator.style.borderRadius = '5px';
    document.body.appendChild(progressIndicator);

    let completedDownloads = 0;

    for (let index = 0; index < images.length; index++) {
        const previewImg = images[index];
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        canvas.width = widthInput.value;
        canvas.height = heightInput.value;

        ctx.drawImage(previewImg, 0, 0, canvas.width, canvas.height);

        // Get original file name and type
        let fileName = previewImg.dataset.fileName || `image_${index + 1}`;
        let fileType = previewImg.dataset.fileType || 'jpeg'; // Default to jpeg if no type
        let mimeType = `image/${fileType}`;

        // compress action time working this code
        if(imgQuality < 1.0){
            if (fileType === "png" || fileType === "bmp") {
                fileType = "jpeg"; // Convert to JPEG
                mimeType = `image/${fileType}`;
            }
        }

        // Convert canvas to a Blob with the selected quality
        // const blob = await new Promise((resolve) => canvas.toBlob(resolve, mimeType, imgQuality));
        // zip.file(fileName, blob); // Add the Blob to the ZIP archive




        // Original file reference for size comparison
        const originalBlob = await fetch(previewImg.src).then(res => res.blob());
        const originalSize = originalBlob.size;

        // Convert canvas to a Blob with the selected quality
        const compressedBlob = await new Promise((resolve) =>{
            if(imgQuality < 1.0){
                canvas.toBlob(resolve, mimeType, imgQuality);
                console.log('compress image promise');
                
            }else{
                canvas.toBlob(resolve, mimeType);
                console.log('resize image compress');
                
            }
        });

        // Check compressed size against the original size
        if (compressedBlob.size > originalSize) {
            if(imgQuality < 1.0){
                alert(
                    `Compressed size of ( ${fileName} ) is greater than the original size. Adding the original image to the ZIP instead.`
                );
                console.log('compress image done');
                zip.file(fileName, originalBlob); // Add original image to ZIP
            }else{
                console.log('resize image done');
                zip.file(fileName, compressedBlob); // Add original image to ZIP
            }
        } else {
            console.log('compress || resize image done');
            
            zip.file(fileName, compressedBlob); // Add compressed image to ZIP
        }




        completedDownloads++;
        progressIndicator.innerText = `Added ${completedDownloads} of ${images.length} images to ZIP...`;
    }

    // Generate the ZIP and trigger download
    zip.generateAsync({ type: "blob" }).then((content) => {
        saveAs(content, "resized_images.zip"); // Use FileSaver.js to download the ZIP
        progressIndicator.innerText = 'Download completed successfully!';
        setTimeout(() => document.body.removeChild(progressIndicator), 2000);
    });
};


// Attach new function to download button
downloadBtn.addEventListener("click", resizeAndDownloadAsZip);





// downloadBtn.addEventListener("click", resizeAndDownloadMultiple);
fileInput.addEventListener("change", loadFile);
uploadBox.addEventListener("click", () => fileInput.click());









