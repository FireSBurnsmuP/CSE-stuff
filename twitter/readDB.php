<?php
   echo "<h2>Twitter Postings:</h2>";
   $link = mysqli_connect('mysql-user','username','passwd','dbname');
   $query = "select * from TwitterPosting";
   if ($result = mysqli_query($link, $query)) {
        
       $row_cnt = mysqli_num_rows($result);      //Determine number of rows in result
       $field_cnt = mysqli_num_fields($result);  //Determine number of columns

       echo "Result set has ".$row_cnt." rows and ".$field_cnt." fields.<BR>";

       while($row=mysqli_fetch_row($result)) {
            echo "Time:$row[1] User:$row[5] Tweet:";
	    echo strtolower($row[6]). "<br>\n";
       }
   }
   flush();
   mysqli_close($link);
?>
