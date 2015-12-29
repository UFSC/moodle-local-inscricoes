<?php

defined('MOODLE_INTERNAL') || die();

function local_inscricoes_extend_settings_navigation(navigation_node $navigation) {
    global $PAGE;

    if (is_a($PAGE->context, 'context_coursecat') && has_capability('local/inscricoes:view', $PAGE->context)) {
        $category_node = $navigation->get('categorysettings');
        $node = navigation_node::create(
                    get_string('title', 'local_inscricoes'),
                    new moodle_url('/local/inscricoes/index.php', array('contextid' => $PAGE->context->id)));
        $category_node->add_node($node);
    }
}
