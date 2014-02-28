<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot.'/local/inscricoes/locallib.php');

class local_inscricoes_renderer extends plugin_renderer_base {

    private static $color1 = '#CECECE';
    private static $color2 = '#DDDDDD';

    private $categoryid = false;
    private $contextid = false;
    private $courses = false;

    private $completions = false;
    private $modinfo = false;
    private $course_grade_items = false;

    public function show_progress_report($context, $group_name='', $days_before=15, $completed_modules=-1, $studentsorderby='name', $coursesorderby='sortorder') {
        global $OUTPUT, $USER;

        $this->categoryid = $context->instanceid;
        $this->contextid = $context->id;
        $this->print_header();

        print $this->heading(get_string('report_progress', 'local_inscricoes') .
                             $OUTPUT->help_icon('report_progress', 'local_inscricoes'));

        $this->courses = local_inscricoes_get_active_courses($this->contextid);
        if(empty($this->courses)) {
            print html_writer::empty_tag('BR');
            print html_writer::tag('h4', get_string('no_courses', 'local_inscricoes'));
        }

        $group = false;
        $groups = local_inscricoes_get_groups(array_keys($this->courses), $context, $USER->id);
        if(empty($groups)) {
            print html_writer::empty_tag('BR');
            print html_writer::tag('h4', get_string('no_group', 'local_inscricoes'));
        } else if(count($groups) > 1) {
            if(empty($group_name) || !isset($groups[$group_name])) {
                $this->show_progress_form($groups, false, $days_before, $completed_modules, $studentsorderby, $coursesorderby);
            } else {
                $group = $groups[$group_name];
            }
        } else {
            $group = reset($groups);
        }

        if($group) {
            $this->initialize($coursesorderby);
            $this->show_progress_form($groups, $group, $days_before, $completed_modules, $studentsorderby, $coursesorderby);
            $this->show_group($group, $days_before, $completed_modules, $studentsorderby, $coursesorderby);
        }

        $this->print_footer();
    }

    public function show_completion_report($context, $csv=false) {
        global $OUTPUT, $DB;

        $this->categoryid = $context->instanceid;
        $this->contextid = $context->id;

        $report = $DB->get_record('inscricoes_reports', array('contextid'=>$this->contextid));
        $this->courses = local_inscricoes_get_active_courses($this->contextid);
        foreach($this->courses AS $courseid=>$course) {
            $course->grade_item = grade_item::fetch_course_item($courseid);
        }

        $param_color1 = array('BGCOLOR'=>self::$color1);
        $param_color2 = array('BGCOLOR'=>self::$color2);

        if($csv) {
            $filename = clean_filename($DB->get_field('course_categories', 'name', array('id'=>$this->categoryid)));
            $csvexport = new csv_export_writer('semicolon');
            $csvexport->set_filename($filename);

            $csvdata = array('ordem', 'Nome', 'Id.Usuário', 'Grupos');
            foreach($this->courses AS $courseid=>$course) {
                $csvdata[] = $course->shortname;
            }
            $csvdata[] = "Aprovado";
            $csvexport->add_data($csvdata);
        } else {
            $this->print_header();

            print $this->heading(get_string('report_completion', 'local_inscricoes') .
                                 $OUTPUT->help_icon('report_progress', 'local_inscricoes'));
            print html_writer::empty_tag('br');
            $this->show_completion_form();

            print html_writer::start_tag('TABLE');
            print html_writer::start_tag('TR');
            print html_writer::tag('TH', '');
            print html_writer::tag('TH', 'Nome', $param_color2);
            print html_writer::tag('TH', 'Id.Usuário', $param_color1);
            print html_writer::tag('TH', 'Grupos', $param_color2);

            $color = self::$color1;
            foreach($this->courses AS $courseid=>$course) {
                $link = html_writer::link(new moodle_url('/course/view.php', array('id'=>$courseid)),
                                        $course->shortname, array('target'=>'_blank'));
                $type = $course->type == 1 ? get_string('mandatory', 'local_inscricoes') : get_string('optional', 'local_inscricoes');
                print html_writer::tag('TH', $link.'<BR>('.$type.')' , array('BGCOLOR'=>$color));
                $color = ($color == self::$color1) ? self::$color2 : self::$color1;
            }
            print html_writer::tag('TH', 'Aprovado' , array('BGCOLOR'=>$color));

            print html_writer::end_tag('TR');
        }

        $students = local_inscricoes_get_all_students($this->contextid);
        $count = 0;
        $count_approved = 0;
        $count_not_approved = 0;

        foreach($students AS $userid=>$user) {
            $count++;
            $name_url = new moodle_url('/user/profile.php', array('id'=>$userid));
            $name = html_writer::link($name_url, $user->fullname, array('target'=>'_blank', 'title'=>'Visualizar perfil de '.$user->fullname));

            $line = html_writer::tag('TD', $name , $param_color2);
            $line .= html_writer::tag('TD', $user->username, $param_color1);
            $groupnames = '<UL><LI>' . implode('</LI><LI>', explode(';', $user->groupnames)) . '</LI></UL>';
            $line .= html_writer::tag('TD', $groupnames, $param_color2);
            $csvdata = array($count, $user->fullname, $user->username, $user->groupnames);

            $count_optional = 0;
            $color = self::$color1;
            $approved = true;
            foreach($this->courses AS $courseid=>$course) {
                $course_item = $course->grade_item;
                $course_grade = new grade_grade(array('itemid'=>$course_item->id, 'userid'=>$userid));
                $course_grade->grade_item =& $course_item;
                $finalgrade = $course_grade->finalgrade;
                $grade = grade_format_gradevalue($finalgrade, $course_item, true);

                if ($course_grade->is_passed($course_item)) {
                    $gradepass = html_writer::tag('font', $grade, array('color'=>'GREEN'));
                    if($course->type == 2) {
                        $count_optional++;
                    }
                } elseif (is_null($course_grade->is_passed($course_item))) {
                    $gradepass = $grade;
                    $approved = false;
                } else {
                    $gradepass = html_writer::tag('font', $grade, array('color'=>'RED'));
                    $approved = false;
                }
                $line .= html_writer::tag('td', $gradepass, array('class'=>'completion-progresscell', 'bgcolor'=>$color));
                $csvdata[] = $grade;
                $color = ($color == self::$color1) ? self::$color2 : self::$color1;
            }

            if($approved && $count_optional >= $report->minoptionalcourses) {
                $yes_no = html_writer::tag('font', 'Sim', array('color'=>'GREEN'));
                $csvdata[] = 'Sim';
                $count_approved++;
            } else {
                $yes_no = html_writer::tag('font', 'Não', array('color'=>'RED'));
                $csvdata[] = 'Não';
                $count_not_approved++;
            }
            $line .= html_writer::tag('td', $yes_no, array('class'=>'completion-progresscell', 'bgcolor'=>$color));

            if($csv) {
                $csvexport->add_data($csvdata);
            } else {
                print html_writer::start_tag('TR');
                print html_writer::tag('td', $count . '.');
                print $line;
                print html_writer::end_tag('TR');
            }
        }

        if($csv) {
            $csvexport->download_file();
            exit;
        } else {
            print html_writer::end_tag('TABLE');
            $this->show_completion_resume($count_approved, $count_not_approved);

            $this->print_footer();
        }

    }

   private function show_completion_resume($count_approved, $count_not_approved) {
        print html_writer::start_tag('DIV', array('align'=>'center'));
        print html_writer::tag('h3', 'Contagem de alunos aprovados e não aprovados');
        print html_writer::start_tag('TABLE');

        print html_writer::start_tag('TR');
        print html_writer::tag('TH', 'Alunos aprovados');
        print html_writer::tag('TH', 'Alunos não aprovados');
        print html_writer::end_tag('TR');

        $params = array('align'=>'center');
        print html_writer::start_tag('TR');
        print html_writer::tag('TD', $count_approved, $params);
        print html_writer::tag('TD', $count_not_approved, $params);
        print html_writer::end_tag('TR');

        print html_writer::end_tag('TABLE');
        print html_writer::end_tag('DIV');
    }

    private function initialize($coursesorderby) {
        $this->completions = array();
        $this->modinfo = array();
        $this->course_grade_items = array();
        foreach($this->courses AS $id=>$course) {
            $this->modinfo[$id] = get_fast_modinfo($course);
            $this->completions[$id] = new completion_info($course);
            $this->course_grade_items[$id] = grade_item::fetch_course_item($id);
        }
    }

    private function show_completion_form() {
        print html_writer::empty_tag('br');
        print html_writer::start_tag('div', array('align'=>'right'));
        $url = new moodle_url('/local/inscricoes/reports/completion.php', array('contextid'=>$this->contextid));
        print html_writer::start_tag('form', array('method'=>'post', 'action'=>$url));

        print html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'csv', 'value'=>'Gerar CSV'));

        print html_writer::end_tag('form');

        print html_writer::end_tag('div');
        print html_writer::empty_tag('br');
    }

    private function show_progress_form($groups, $selected_group=false, $days_before=15, $completed_modules=-1, $studentsorderby='name', $coursesorderby='sortorder') {
        print html_writer::empty_tag('br');
        print html_writer::start_tag('div', array('align'=>'left'));
        $url = new moodle_url('/local/inscricoes/reports/progress.php', array('contextid'=>$this->contextid));
        print html_writer::start_tag('form', array('method'=>'post', 'action'=>$url));

        $groups_menu = array();
        foreach($groups AS $name=>$grp) {
            $groups_menu[$name] = $name;
        }
        print get_string('group') . ': ';
        $group_name = $selected_group ? $selected_group->name : '';
        print html_writer::select($groups_menu, 'group_name', $group_name);

        $days = array(5=>5, 10=>10, 15=>15, 20=>20, 30=>30, 60=>60, 90=>90);
        print '&nbsp;Dias atrás:';
        print html_writer::select($days, 'days_before', $days_before);

        print html_writer::empty_tag('br');
        print 'Critério de ordenação de estudantes:';
        $order = array('name'=>'Nome', 'lastaccess'=>'Último acesso');
        print html_writer::select($order, 'studentsorderby', $studentsorderby);

        print html_writer::empty_tag('br');
        print 'Critério de ordenação de módulos:';
        $coursesorder = array('fullname'=>'Nome', 'sortorder'=>'De apresentação no Moodle');
        print html_writer::select($coursesorder, 'coursesorderby', $coursesorderby);

        print html_writer::empty_tag('br');
        print 'Quais estudantes mostrar:';
        $n = $this->completions ? count($this->completions) : 4;
        $modules = array(0=>'Com nenhum módulo completado', 1=>'Com 1 módulo completado');
        for($i=2; $i <= $n; $i++) {
            $modules[$i] = "Com {$i} módulos completados";
        }
        $modules[-1]='Todos';
        print html_writer::select($modules, 'completed_modules', $completed_modules);

        print html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'show', 'value'=>s(get_string('show'))));
        print html_writer::end_tag('form');

        print html_writer::end_tag('div');
        print html_writer::empty_tag('br');
    }

    private function show_group($group, $days_before=15, $num_mod_completed=-1, $studentsorderby='name') {
        global $OUTPUT;

        print $this->heading('Grupo: ' . $group->name);
        print html_writer::empty_tag('BR');

        print html_writer::start_tag('TABLE');
        print html_writer::start_tag('TR');
        print html_writer::tag('TH', '', array('ROWSPAN'=>'3'));
        print html_writer::tag('TH', '', array('ROWSPAN'=>'3', 'BGCOLOR'=>self::$color2));
        print html_writer::tag('TH', 'Nome', array('ROWSPAN'=>'3', 'BGCOLOR'=>self::$color2, 'style'=>'vertical-align:bottom;'));
        print html_writer::tag('TH', 'Ações', array('ROWSPAN'=>'3', 'BGCOLOR'=>self::$color1, 'style'=>'vertical-align:bottom;'));
        print html_writer::tag('TH', 'Acessos', array('ROWSPAN'=>'3', 'BGCOLOR'=>self::$color2, 'style'=>'vertical-align:bottom;'));

        $color = self::$color1;
        foreach($this->courses AS $courseid=>$course) {
            $criteria = $this->completions[$courseid]->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
            $link = html_writer::link(new moodle_url('/course/view.php', array('id'=>$courseid)),
                                    $course->shortname, array('target'=>'_blank'));
            $type = $course->type == 1 ? get_string('mandatory', 'local_inscricoes') : get_string('optional', 'local_inscricoes');
            print html_writer::tag('TH', $link.'<BR>('.$type.')' , array('COLSPAN'=>count($criteria)+1, 'BGCOLOR'=>$color));
            $color = ($color == self::$color1) ? self::$color2 : self::$color1;
        }

        print html_writer::end_tag('TR');

        // Mostra os nomes das atividades
        print html_writer::start_tag('TR');

        $color = self::$color1;
        foreach($this->courses AS $courseid=>$course) {
            $criteria = $this->completions[$courseid]->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
            $params = array('scope'=>'col', 'class'=>'colheader criterianame', 'BGCOLOR'=>$color);
            foreach ($criteria as $criterion) {
                $text = html_writer::tag('SPAN', $this->modinfo[$courseid]->cms[$criterion->moduleinstance]->name, array('class'=>'completion-criterianame'));
                print html_writer::tag('TH', $text, $params);
            }
            $text = html_writer::tag('SPAN', get_string('finalgrade','local_inscricoes'), array('class'=>'completion-criterianame'));
            print html_writer::tag('TH', $text , $params);

            $color = ($color == self::$color1) ? self::$color2 : self::$color1;
        }

        print html_writer::end_tag('TR');

        // Mostra os ícones das atividades
        print html_writer::start_tag('TR');
        $color = self::$color1;
        foreach($this->courses AS $courseid=>$course) {
            $criteria = $this->completions[$courseid]->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
            $params = array('class'=>'criteriaicon', 'BGCOLOR'=>$color);
            foreach ($criteria as $criterion) {
                $icon = $OUTPUT->pix_url('icon', $criterion->module);
                $iconlink = new moodle_url("/mod/{$criterion->module}/view.php", array('id'=>$criterion->moduleinstance));
                $icontitle = $this->modinfo[$courseid]->cms[$criterion->moduleinstance]->name;
                $iconalt = get_string('modulename', $criterion->module);

                $img_tag = html_writer::empty_tag('img', array('src'=>$icon, 'class'=>'icon', 'alt'=>$iconalt));
                $href_tag = html_writer::link($iconlink, $img_tag, array('title'=>$icontitle, 'target'=>'_new'));
                print html_writer::tag('TH', $href_tag , $params);
            }

            $icon = $OUTPUT->pix_url('i/grades', '');
            $iconlink = new moodle_url("/grade/edit/tree/index.php", array('id'=>$courseid, 'showadvanced'=>0));
            $img_tag = html_writer::empty_tag('img', array('src'=>$icon, 'class'=>'icon'));
            $href_tag = html_writer::link($iconlink, $img_tag, array('title'=>'Categorias e itens de notas', 'target'=>'_new'));
            print html_writer::tag('TH', $href_tag , $params);
            $color = ($color == self::$color1) ? self::$color2 : self::$color1;
        }
        print html_writer::end_tag('TR');

        $param_color1 = array('BGCOLOR'=>self::$color1);
        $param_color2 = array('BGCOLOR'=>self::$color2);

        $students = local_inscricoes_get_students($this->contextid, $group->str_groupids, $days_before, $studentsorderby);
        $count = 0;
        $user_count = array();
        for($i=0; $i <= count($this->completions); $i++) {
            $user_count[$i] = 0;
        }
        foreach($students AS $userid=>$user) {
            $user->courseids = explode(',', $user->str_courseids);
            $name_url = new moodle_url('/user/profile.php', array('id'=>$userid));
            $name = html_writer::link($name_url, $user->fullname, array('target'=>'_blank', 'title'=>'Visualizar perfil de '.$user->fullname));
            $message_img = html_writer::empty_tag('img', array('src'=>new moodle_url('/pix/t/message.gif'), 'class'=>'icon', 'title'=>'Enviar mensagem para '.$user->fullname));
            $message_url = new moodle_url('/message/index.php', array('user2'=>$userid));
            $message_link = html_writer::link($message_url, $message_img, array('target'=>'_blank'));

            $line = '';
            $line .= html_writer::tag('TD', $message_link , $param_color2);
            $line .= html_writer::tag('TD', $name , $param_color2);
            if(isset($user->first_access)) {
                $describe = "Ações nos últimos {$days_before} dias: {$user->recent_actions} | Total de ações: {$user->count_actions}";
                $text = html_writer::tag('SPAN', $user->recent_actions . ' | ' . $user->count_actions, array('title'=>$describe));
                $line .= html_writer::tag('TD', $text, $param_color1);

                $str_first = empty($user->first_access) ? '-' : date('d/m/Y', $user->first_access);
                $str_last = empty($user->last_access) ? '-' : date('d/m/Y', $user->last_access);
                $describe = "Primeiro acesso: {$str_first} | Último acesso: {$str_last}";
                $text = html_writer::tag('SPAN', $str_first . '<BR>' . $str_last, array('title'=>$describe));
                $line .= html_writer::tag('TD', $text, $param_color2);
            } else {
                $line .= html_writer::tag('TD', '', $param_color1);
                $line .= html_writer::tag('TD', '', $param_color2);
            }
            $color = self::$color1;
            $completed = 0;
            foreach($this->completions AS $courseid=>$completion) {
                $line .= $this->show_user_activity_completion($courseid, $completion, $user, $color);

                if(in_array($courseid, $user->courseids)) {
                    $course_item = $this->course_grade_items[$courseid];
                    $course_grade = new grade_grade(array('itemid'=>$course_item->id, 'userid'=>$userid));
                    $course_grade->grade_item =& $course_item;
                    $finalgrade = $course_grade->finalgrade;
                    $grade = grade_format_gradevalue($finalgrade, $course_item, true);

                    if ($course_grade->is_passed($course_item)) {
                        $gradepass = html_writer::tag('font', $grade, array('color'=>'GREEN'));
                        $completed++;
                    } elseif (is_null($course_grade->is_passed($course_item))) {
                        $gradepass = $grade;
                    } else {
                        $gradepass = html_writer::tag('font', $grade, array('color'=>'RED'));
                    }
                    $line .= html_writer::tag('td', $gradepass, array('class'=>'completion-progresscell', 'bgcolor'=>$color));
                } else {
                    $line .= html_writer::tag('td', '?', array('class'=>'completion-progresscell', 'bgcolor'=>$color, 'title'=>'Não inscrito no módulo'));
                }

                $color = ($color == self::$color1) ? self::$color2 : self::$color1;
            }
            if($num_mod_completed == -1 || $completed == $num_mod_completed) {
                $count++;
                print html_writer::start_tag('TR');
                print html_writer::tag('td', $count . '.');
                print $line;
                print html_writer::end_tag('TR');
            }
            $user_count[$completed]++;
        }

        print html_writer::end_tag('TABLE');
        $this->show_progress_resume($user_count);
    }

    private function show_progress_resume($user_count) {
        print html_writer::start_tag('DIV', array('align'=>'center'));
        print html_writer::tag('h3', 'Contagem de alunos por número de módulos completados');
        print html_writer::start_tag('TABLE');

        print html_writer::start_tag('TR');
        print html_writer::tag('TH', 'Num. módulos completados');
        print html_writer::tag('TH', 'Num. alunos');
        print html_writer::end_tag('TR');

        $params = array('align'=>'center');
        foreach($user_count AS $num_mod=>$count) {
            print html_writer::start_tag('TR');
            print html_writer::tag('TD', $num_mod, $params);
            print html_writer::tag('TD', $count, $params);
            print html_writer::end_tag('TR');
        }

        print html_writer::end_tag('TABLE');
        print html_writer::end_tag('DIV');
    }

    private function show_user_activity_completion($courseid, $completion, $user, $color) {
        global $OUTPUT;

        $criteria = $completion->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
        $output = '';
        if(in_array($courseid, $user->courseids)) {
            $modinfo = $this->modinfo[$courseid];
            foreach ($criteria as $criterion) {
                // Load activity
                $activity = $modinfo->cms[$criterion->moduleinstance];

                $grade_item = grade_item::fetch(array('courseid'=>$criterion->course, 'itemtype'=>'mod',
                                                      'itemmodule'=>$criterion->module, 'iteminstance'=>$activity->instance));
                if($grade_item && $grade_item->gradepass > 0) {
                    $mod_grade = new grade_grade(array('itemid'=>$grade_item->id, 'userid'=>$user->id));
                    $mod_grade->grade_item =& $grade_item;
                    $finalgrade = $mod_grade->finalgrade;
                    $grade = grade_format_gradevalue($finalgrade, $grade_item, true);

                    if ($mod_grade->is_passed($grade_item)) {
                        // $content = html_writer::tag('font', $grade, array('color'=>'GREEN'));
                        $content = html_writer::tag('font', $grade, array('color'=>'#4A4A4A'));
                    } elseif (is_null($mod_grade->is_passed($grade_item))) {
                        $content = $grade;
                    } else {
                        $content = html_writer::tag('font', $grade, array('color'=>'RED'));
                    }
                } else {
                    $criteria_completion = $completion->get_user_completion($user->id, $criterion);
                    $is_complete = $criteria_completion->is_complete();

                    // Get progress information and state
                    if ($is_complete) {
                        $date = userdate($criteria_completion->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));

                        if (isset($user->progress) && array_key_exists($activity->id, $user->progress)) {
                            $thisprogress = $user->progress[$activity->id];
                            $state = $thisprogress->completionstate;
                        } else {
                            $state = COMPLETION_COMPLETE;
                        }
                    } else {
                        $date = '';
                        $state = COMPLETION_INCOMPLETE;
                    }

                    // Work out how it corresponds to an icon
                    switch($state) {
                        case COMPLETION_INCOMPLETE    : $completiontype = 'n';    break;
                        case COMPLETION_COMPLETE      : $completiontype = 'y';    break;
                        case COMPLETION_COMPLETE_PASS : $completiontype = 'pass'; break;
                        case COMPLETION_COMPLETE_FAIL : $completiontype = 'fail'; break;
                    }

                    $auto = $activity->completion == COMPLETION_TRACKING_AUTOMATIC;
                    $completionicon = 'completion-'.($auto ? 'auto' : 'manual').'-'.$completiontype;

                    $describe = get_string('completion-'.$completiontype, 'completion');
                    $a = new StdClass();
                    $a->state     = $describe;
                    $a->date      = $date;
                    $a->user      = $user->fullname;
                    $a->activity  = strip_tags($activity->name);
                    $fulldescribe = get_string('progress-title', 'completion', $a);

                    $icon = $OUTPUT->pix_url('i/'.$completionicon);
                    $content = html_writer::empty_tag('img', array('src'=>$icon, 'class'=>'icon', 'alt'=>$describe, 'title'=>$fulldescribe));
                }

                $output .= html_writer::tag('td', $content , array('class'=>'completion-progresscell', 'BGCOLOR'=>$color));
            }
        } else {
            foreach ($criteria as $criterion) {
                $output .= html_writer::tag('td', '?', array('BGCOLOR'=>$color, 'title'=>'Não inscrito no módulo'));
            }
        }
        return $output;
    }

    public function print_header() {
        global $OUTPUT, $PAGE;

        echo $OUTPUT->header();

        $PAGE->requires->js('/local/inscricoes/reports/textrotate.js');
        $PAGE->requires->js_function_call('textrotate_init', null, true);
    }

    public function print_footer() {
        global $OUTPUT;

        echo $OUTPUT->footer();
    }
}
