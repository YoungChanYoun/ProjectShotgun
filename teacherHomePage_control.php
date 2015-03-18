<?php
   if( isset( $_GET['action'] ) )
   {
      if( $_GET['action'] == "delete" )
      {
         delete_test( $_GET['test_id'] );
      }
      if( $_GET['action'] == "modify" )
      {
         modify_test( $_GET['test_id'] );
      }
   }
   if( isset( $_GET['section_id'] ) )
   {
      get_test_list($_GET['section_id']);
   }


   //TeacherHomePage.php
   function get_class_list()
   {
      include 'db_connection.php';
      $sql_command = "SELECT `course`.`COURSE_NO`, `section`.`SECTION_NO`, `section`.`SECTION_ID`, `course`.`DESCRIPTION`\n"
         . "FROM `section`\n"
         . " LEFT JOIN `cs414_team2`.`course` ON `section`.`COURSE_NO` = `course`.`COURSE_NO` \n"
         . "\n"
         . "";

      $sql_result = mysqli_query($connection, $sql_command);
      mysqli_close($connection);

      $class_list = [];

      for($i = 1; $i <= @mysqli_num_rows($sql_result); $i++)
      {  //[0]-COURSE_NO [1]-SECTION_NO [2]-SECTION_ID [3]-COURSE_DESCRIPTION
         $row = mysqli_fetch_row($sql_result);
         echo '<tr>';
            echo '<td id="courseTD"type="submit" onclick="get_class_test(' . $row[2] . ')">';
               echo ($row[0] . ' - ' . $row[1]);
            echo '</td>';
         echo '</tr>';
         $class_list += array($row[2] => ($row[0]."-".$row[1]." ".$row[3]));
      }

      return $class_list;
   }

   //TeacherHomePage.php
   function get_test_list($section_no)
   {
      include 'db_connection.php';
      $sql_command = "SELECT `SECTION_ID`,`TEST_NAME`, `PUBLISHED`, `START_DATE`, `END_DATE`, `TEST_ID`\n"
         . "FROM `test`\n"
         . "WHERE `SECTION_ID` =" . $section_no;

      $sql_result = mysqli_query($connection, $sql_command);


      if( @mysqli_num_rows($sql_result) != 0)
      {
         for($i=1; $i<=mysqli_num_rows($sql_result); $i++)
         {
            $row = mysqli_fetch_row($sql_result);
            //echo '<tr><td id="testTD"><form action="" method="post">';
            echo '<tr><td id="testTD">';
            echo     "<span id='testTitle'>" . $row[1] . "</span>";
            echo     "<span id='button'>";
            echo        "<button type='submit' value=$row[5] id='editButton' name='editButton' onclick='modify_test($row[5])'></button>";
            echo        "<button type='submit' value=$row[5] id='deleteButton' name='deleteButton' onclick='delete_test($row[5])'></button>";
            echo        "<button type='submit' value=$row[5] id='gradeButton' name='gradeButton' onclick='grade_test()'></button>";
            echo     "</span><br />";
            echo     "Date Available: ";
            echo        $row[3] . " ~ " . $row[4] . "<br />";
            echo     'Status: ' . (($row[2] == 1)? 'Published' : 'Not Published') . "<br />";
            echo     'Class Average: ' . "not set yet";
            //echo '</form></td></tr>';
            echo '</td></tr>';
         }
      }
      else
         echo "no data";

      mysqli_close($connection);
   }

   //TeacherHomePage.php
   function modify_test($test_id)
   {
      header('Location: ./testMakingPage.php?test_no='.$test_id);
   }

   //TeacherHomePage.php
   function delete_test($test_id)
   {
      include 'db_connection.php';
      $sql_command = "CALL DELETE_TEST(". $test_id .")";

      $sql_result = mysqli_query($connection, $sql_command);
      mysqli_close($connection);

   }


   // ======================================================================================== //
   //                                                                                          //
   // ======================================================================================== //


   //TestMakingPage.php
   function load_test($test_no)
   {
      include 'db_connection.php';
      $sql_command =
         "SELECT TEST_NAME, START_DATE, END_DATE, TIME_LIMIT, PLEDGE, SECTION.SECTION_ID, COURSE_NO, SECTION_NO\n"
         . "FROM TEST, SECTION\n"
         . "WHERE TEST.SECTION_ID = SECTION.SECTION_ID\n"
         . "AND TEST_ID = " . $test_no . ";";

      $sql_result = mysqli_query($connection, $sql_command);
      $row = mysqli_fetch_row($sql_result);
      echo  load_question_info($row);



      if( @mysqli_num_rows($sql_result) != 0 ) {
         $sql_command = "select * from question where test_id = " . $test_no . ";";
         $sql_result  = mysqli_query($connection, $sql_command);
         $question_data = array();

         if (@mysqli_num_rows($sql_result) != 0) {
            for ($i = 1; $i <= mysqli_num_rows($sql_result); $i++) {
               $row = mysqli_fetch_row($sql_result);
               load_question_form($row);
               $question_data += array($row[0], $row[4], $row[5]); //Q_ID, Que, Pt
            }
            echo load_question_refresh();
         }
      }
      mysqli_close($connection);
   }

   function load_question_info($data)
   {
      return
         '<script type="text/javascript">'.
            '$("#testName").val("' . $data[0] . '");' .
            '$("#test_inst_text").val("' . $data[0] . '");' .
            '$("#pledge_text").val("' . $data[4] . '");' .
            '$("#startDate").val("' . substr($data[1], 0, 10) . '");' .
            '$("#endDate").val("' . substr($data[2], 0, 10) . '");' .
            '$("#startTime").val("' . substr($data[1], 11, 18) . '");' .
            '$("#endTime").val("' . substr($data[2], 11, 18) . '");' .
            '$("#hours").val("' . intval(substr($data[3], 0, 2)) . '");' .
            '$("#minutes").val("' . intval(substr($data[3], 3, 5)) . '");' .
            '$("#courseNo").val("' . preg_replace('/\s+/', '', $data[6]) . '");' .
            '$("#sectionNo").val("' . $data[5] . '");' .
         '</script>';
   }

   function load_question_form($data)
   {
      $q_types = ['True/False', 'Multiple Choice', 'Many Choice', 'Short Answer', 'Essay'];
      $q_type = array_search($data[3], $q_types);
      echo '<li class="ui-state-default tess">';
      echo '<span>::</span> ';
      echo     load_question_type($q_type, $data[4], $data[5], $data[0]);
      echo     ($q_type == 1 || $q_type == 2 ? load_question_option(($q_type == 1 ? "radio" : "checkbox"), $data[0]) : "");
      echo     ($q_type == 0 || $q_type == 3 || $q_type == 4 ? load_question_extra() : "");
      echo '</li>';
   }

   function load_question_type($q_type, $q_text, $q_pt, $q_id)
   {
      if($q_type == 1 || $q_type == 3)
      {
         include 'db_connection.php';
         $sql_command = "SELECT * FROM `answer` WHERE QUES_ID = " . $q_id . ";";
         $sql_result = mysqli_query($connection, $sql_command);
         $row = mysqli_fetch_row($sql_result);
         mysqli_close($connection);
      }

      $form_array = ['True/False Question', 'Multiple Choice Question', 'Many Choice Question', 'Short Answer Question', 'Essay Question'];
      $form_text_array =
         [
            //Index:0 - True/False Question
            $form_array[$q_type] .
            '<input type="text" maxlength="3" size="4" style="float: right;" value="'.$q_pt.'"><qp style="float:right;"> Point-&nbsp;</qp>'.
            '<textarea required rows="3" placeholder="True/False Question">'.$q_text.'</textarea>'.
            '<input type="radio" style="margin-left: 23%;"' . ($q_type==1||@$row[2]? 'checked': '') . '> True'.
            '<input type="radio" style="margin-left: 23%;"' . ($q_type==1||@!$row[2]? 'checked': '') . '> False',

            //Index:1 - Multiple Choice Question
            $form_array[$q_type] .
            '<input type="text" maxlength="3" size="4" style="float: right;" value="'.$q_pt.'"><qp style="float:right;"> Point-&nbsp;</qp>'.
            //'<input type="button" value="Option" onclick="add_option(this);"><br>'.
            '<textarea required rows="3" placeholder="Multiple Choice Question">'.$q_text.'</textarea>' ,

            //Index:2 - Many Choices
            $form_array[$q_type] .
            '<input type="text" maxlength="3" size="4" style="float: right;" value="'.$q_pt.'"><qp style="float:right;"> Point-&nbsp;</qp>'.
            //'<input type="button" value="Option" onclick="add_option(this);"><br>'.
            '<textarea required rows="3" placeholder="Many Choice Question">'.$q_text.'</textarea>',

            //Index:3 - Short Answer Question
            $form_array[$q_type] .
            '<input type="text" maxlength="3" size="4" style="float: right;" value="'.$q_pt.'"><qp style="float:right;"> Point-&nbsp;</qp>'.
            '<textarea required rows="3" placeholder="Short Answer Question">'.$q_text.'</textarea>'.
            'Answer: <input type="text"  maxlength="50" size="55" value="' . @$row[2] . '">',

            //Index:4 - Essay
            $form_array[$q_type] .
            '<input type="text" maxlength="3" size="4" style="float: right;" value="'.$q_pt.'"><qp style="float:right;"> Point-&nbsp;</qp>'.
            '<textarea required rows="4" placeholder="Essay Question">'.$q_text.'</textarea><br>',

            //Disabled
            $form_array[$q_type] .
            '<hr align="left" width="100%" />'.
            '<textarea required rows="4" placeholder="Type Instruction"></textarea>'

            //'<textarea required name="ques_random" rows="4" placeholder="Nothing Nothing"></textarea>'
         ];
      return $form_text_array[$q_type];
   }

   function load_question_refresh()
   {
      return   '<script type="text/javascript">'.
                  'resetQnum();'.
                  '$("#sortable2").css({"height": "auto"});'.
                  '$("#sortable2").css("background-image", "none");'.
               '</script>';
   }

   function load_question_extra()
   {
      $del_button =
         '<button class="bin_button" type="button" onmouseover="recy_onHover(this);" onmouseout="recy_offHover(this);" onclick="removeQ(this);">' .
            '<input type="image" width="100%" height="100%" src="./images/recycle_close.jpeg">'  .
         '</button><br>';

      return $del_button;
   }

   function load_question_option($o_type, $q_id)
   {
      include 'db_connection.php';
      $sql_command = "SELECT * FROM `answer` WHERE QUES_ID = " . $q_id . ";";
      $sql_result = mysqli_query($connection, $sql_command);
      mysqli_close($connection);
      $row = array();
      for ($i = 0; $i < mysqli_num_rows($sql_result); $i++) {
         $row[$i] = mysqli_fetch_row($sql_result);
      }

       $mul_op =
         '<table class="q_options">' .
            '<tr>' .
               '<td>' .
                  '<input type="' . $o_type . '" value="A"' . ($row[0][3]? "checked": "") . '>' .
                  '<span>A</span> <input type="text" value="' . $row[0][2] . '">' .
               '</td>' .
               '<td>' .
                  '<input type="' . $o_type . '" value="B"' . ($row[1][3]? "checked": "") . '>' .
                  '<span>B</span> <input type="text" value="' . $row[1][2] . '">' .
               '</td>' .
            '</tr>' .
            '<tr>' .
               '<td>' .
                  '<input type="' . $o_type . '" value="C"' . ($row[2][3]? "checked": "") . '>' .
                  '<span>C</span> <input type="text" value="' . $row[2][2] . '">' .
               '</td>' .
               '<td>' .
                  '<input type="' . $o_type . '" value="D"' . ($row[3][3]? "checked": "") . '>' .
                  '<span>D</span> <input type="text" value="' . $row[3][2] . '">' .
               '</td>' .
               '<td>' .
                  '<button class="bin_button" type="button"' .
                     'onmouseover="recy_onHover(this);" onmouseout="recy_offHover(this);"' .
                     'onclick="removeQ(this.parentNode.parentNode.parentNode.parentNode);">' .
                     '<input type="image" width="100%" height="100%" src="./images/recycle_close.jpeg">' .
                  '</button><br>' .
               '</td>' .
            '</tr>' .
         '</table>';

      return $mul_op;
   }





?>