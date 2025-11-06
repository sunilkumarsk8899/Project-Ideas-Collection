<?php
$title = 'Image Background Remove';
$show = false;
include('header.php');
?>


<style>
  div#preview .image-wrapper canvas {
    width: 100%;
  }

  div#preview .image-wrapper img {
    width: 100%;
  }

  .upload-box-remove {
    border: 2px dashed #ccc;
    text-align: center;
    padding: 20px;
    cursor: pointer;
  }

  .image-wrapper {
    display: inline-block;
    position: relative;
    margin: 10px;
  }

  .delete-button {
    position: absolute;
    top: 5px;
    right: 5px;
    background-color: #EFEFEF;
    color: #927dfc;
    border: none;
    cursor: pointer;
  }

  .loader {
    margin: 10px 0;
  }

  .img-cards {
    display: flex;
    flex-wrap: wrap;
  }
</style>



<div class="wrapper" style="margin-top: 10vh;">
  <div class="upload-box-remove">
    <input type="file" id="fileInput" accept="image/*" hidden multiple>
    <img src="upload-icon.svg" alt="">
    <p>Browse File to Upload Remove Background </p>
  </div>

  <!-- this is hide div -->
  <div class="content">
    <div class="row sizes hideDiv">
      <div class="column width">
        <label>Width</label>
        <input type="number">
      </div>
      <div class="column height">
        <label>Height</label>
        <input type="number">
      </div>
    </div>
    <div class="row checkboxes hideDiv">
      <div class="column ratio text-center">
        <input type="checkbox" id="ratio" style="margin-top: 5px;" checked>
        <label for="ratio">Lock aspect ratio</label>
      </div>
    </div>





    <button class="download-btn" id="downloadZip">Download Remove - BG Image</button>

  </div>




</div>


<div class="row" id="error_msg" style="text-align: center;margin-top: 1%;color: darkred;font-size: 22px;text-transform: capitalize;font-weight: 600;display:none;">
  <div class="col">
    <p id=""></p>
  </div>
</div>





<div class="row m-0">
  <div class="col">
    <div class="img-cards" id="preview" style="">
    </div>
  </div>
</div>



<script>
  const uploadDiv = document.querySelector('.upload-box-remove');
  const fileInput = document.getElementById('fileInput');
  const preview = document.getElementById('preview');
  const downloadZipButton = document.getElementById('downloadZip');

  const processedImages = [];

  const handleFiles = (files) => {
    const fileArray = Array.from(files);
    if (!fileArray.length) return;

    fileArray.forEach((file) => processImage(file));
  };


  /** if request greater than 50 in remove.bg api because api monthly limit is 50 requests */
  function show_error_msg(is_show){
    let error_msg = document.querySelector('#error_msg');
    error_msg.style.display = is_show ? "block" : "none";
    error_msg.querySelector('p').textContent = "Payment is Pandding because you reached out 50 request this month";
  }

  const processImage = (file) => {

    const mainwrapper = document.querySelector('.wrapper');
    mainwrapper.style.height = 'auto';
    document.querySelector('.content').style.opacity = 1;
    document.querySelector('.content').style.pointerEvents = 'auto';


    show_error_msg(false);


    const wrapper = document.createElement('div');
    wrapper.classList.add('image-wrapper');

    const loader = document.createElement('div');
    loader.textContent = 'Processing...';
    loader.classList.add('loader');

    const deleteButton = document.createElement('button');
    deleteButton.textContent = 'X';
    deleteButton.classList.add('delete-button');

    wrapper.appendChild(loader);
    wrapper.appendChild(deleteButton);
    preview.appendChild(wrapper);

    const fileExtension = file.name.split('.').pop();
    const fileNameWithoutExt = file.name.replace(/\.[^/.]+$/, "");

    const formData = new FormData();
    formData.append('image_file', file);

    fetch('https://api.remove.bg/v1.0/removebg', {
        method: 'POST',
        headers: {
          'X-Api-Key': '4gitG9BJEjP5iVeWgDmXwonn',
        },
        body: formData,
      })
      .then(response => {
        if (!response.ok) {
          
          show_error_msg(true);

          throw new Error(`Error: ${response.status} ${response.statusText}`);
        }
        return response.blob();
      })
      .then(blob => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        const objectURL = URL.createObjectURL(blob);
        img.src = objectURL;

        img.onload = () => {
          canvas.width = img.width;
          canvas.height = img.height;
          ctx.drawImage(img, 0, 0);
          loader.remove();
          wrapper.appendChild(canvas);

          processedImages.push({
            name: `${fileNameWithoutExt}-removed-bg.${fileExtension}`,
            dataUrl: canvas.toDataURL(`image/${fileExtension}`)
          });

          addManualEditing(canvas, ctx);
        };
      })
      .catch(() => {
        loader.remove();

        // Show the original image with a red border if background removal fails
        const img = document.createElement('img');
        const reader = new FileReader();

        reader.onload = () => {
          img.src = reader.result;
          img.style.border = "2px solid red";
          wrapper.appendChild(img);
        };

        reader.readAsDataURL(file);
      });

    deleteButton.addEventListener('click', () => {
      wrapper.remove();
      processedImages.splice(
        processedImages.findIndex(img => img.name === `${fileNameWithoutExt}-removed-bg.${fileExtension}`),
        1
      );
    });
  };

  const addManualEditing = (canvas, ctx) => {
    let isDrawing = false;

    const startDrawing = (e) => {
      isDrawing = true;
      draw(e);
    };

    const stopDrawing = () => {
      isDrawing = false;
      ctx.beginPath();
    };

    const draw = (e) => {
      if (!isDrawing) return;

      const rect = canvas.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      ctx.globalCompositeOperation = 'destination-out';
      ctx.beginPath();
      ctx.arc(x, y, 10, 0, Math.PI * 2);
      ctx.fill();
    };

    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
  };

  const downloadZip = () => {
    if (!processedImages.length) {
      alert("No processed images to download.");
      return;
    }

    const zip = new JSZip();

    processedImages.forEach(image => {
      const base64Data = image.dataUrl.split(',')[1];
      zip.file(image.name, base64Data, {
        base64: true
      });
    });

    zip.generateAsync({
        type: "blob"
      })
      .then(blob => {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = "processed-images.zip";
        link.click();
      });
  };

  // Drag and Drop Functionality
  uploadDiv.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadDiv.style.borderColor = '#000';
  });

  uploadDiv.addEventListener('dragleave', () => {
    uploadDiv.style.borderColor = '#ccc';
  });

  uploadDiv.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadDiv.style.borderColor = '#ccc';
    const files = e.dataTransfer.files;
    handleFiles(files);
  });

  fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
  downloadZipButton.addEventListener('click', downloadZip);
  uploadDiv.addEventListener('click', () => fileInput.click());
</script>

<?php
include('footer.php');
?>