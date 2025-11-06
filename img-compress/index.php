<?php
  $title = 'Image Resize';
  $show = true;
  include('header.php'); 
?>




    <div class="wrapper" style="margin-top: 10vh;" >
      <div class="upload-box">
        <input type="file" accept="image/*" hidden multiple >
        <img src="upload-icon.svg" alt="">
        <p>Browse File to Upload Resize </p>
      </div>
      <div class="content">
        <div class="row sizes">
          <div class="column width">
            <label>Width</label>
            <input type="number">
          </div>
          <div class="column height">
            <label>Height</label>
            <input type="number">
          </div>
        </div>
        <div class="row checkboxes">
          <div class="column ratio text-center" >
            <input type="checkbox" id="ratio" style="margin-top: 5px;" checked>
            <label for="ratio">Lock aspect ratio</label>
          </div>
          <!-- <div class="column quality">
            <input type="checkbox" id="quality">
            <label for="quality">Reduce quality</label>
          </div> -->
        </div>

<!-- 
        <div class="column quality_size">
            <select name="quality_size" id="quality_size">
              <?php
                $num = 1;
                while($num <= 9){
                  $selected = '';
                  if($num == 5){
                    $selected = 'selected';
                  }
                  echo "<option value='0.$num' $selected>".$num."0%</option>";
                  $num++;
                }
              ?>
            </select>
        </div> -->



        <button class="download-btn">Download Image</button>
      </div>






    </div>

    <div class="row m-0">
        <div class="col">
            <div class="img-cards" style="">
            </div>
        </div>
    </div>

 



  <?php include('footer.php'); ?>
