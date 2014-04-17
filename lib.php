<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/inscricoes/locallib.php');

function local_inscricoes_extends_settings_navigation(navigation_node $navigation) {
    global $PAGE;

    if (is_a($PAGE->context, 'context_coursecat') && has_capability('local/inscricoes:configure_activity', $PAGE->context)) {
        $category_node = $navigation->get('categorysettings');
        if(!$father_node = $category_node->get('curriculumcontrol', navigation_node::TYPE_CONTAINER)) {
            $father_node = $category_node->add(get_string('curriculumcontrol', 'local_inscricoes'), null,
                                               navigation_node::TYPE_CONTAINER, null, 'curriculumcontrol');
        }

        $node_key = 'node100';
        $before_key = null;
        $children = $father_node->get_children_key_list();
        foreach($children AS $child) {
            if($child > $node_key) {
                $before_key = $child;
                break;
            }
        }

        $node = navigation_node::create(
                    get_string('pluginname', 'local_inscricoes'),
                    new moodle_url('/local/inscricoes/index.php', array('contextid' => $PAGE->context->id)),
                    navigation_node::TYPE_CUSTOM, null, $node_key);
        $father_node->add_node($node, $before_key);
    }
}
