<?php
// var_dump($_SESSION);die();
if(!isset($_SESSION['user_id'])){
 ?>
    <script>
        window.location ="<?php echo baseurl('login.php');?>";
    </script>
   <?php
}
 ?>
