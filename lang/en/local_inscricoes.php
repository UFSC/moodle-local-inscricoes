<?php // $Id$

$string['pluginname'] = 'Sistema de Inscrições';
$string['pluginname_desc'] = 'Define as rotinas de recepção de dados do Sistema de Inscrições';

$string['ws_inscricoes_url'] = 'URL do Sistema de Inscrições';
$string['ws_inscricoes_login'] = 'Id. Usuario WS do Sistema de Inscrições';
$string['ws_inscricoes_password'] = 'Senha WS do Sistema de Inscrições';

$string['empty_role_edition'] = '10-Empty role or edition';
$string['activity_not_configured'] = '11-Activity not configured';
$string['category_unknown'] = '12-Category unknown';
$string['not_coursecat_context'] = '13-Context is not a course category context';
$string['activity_not_enable'] = '14-Activity not enable';
$string['role_unknown'] = '15-Role unknown';
$string['role_invalid'] = '16-Role invalid';
$string['edition_unknown'] = '17-Edition unknown';
$string['ok'] = '1-OK';
$string['answer_text'] = '1-OK or n-error_description';

$string['finalgrade'] = 'Nota Final';
$string['no_group'] = 'Não foram localizados grupos nos módulos indicados na grade curricular deste curso.<BR>Verifique com o responsável pelo curso se os grupos foram corretamente configurada.';
$string['no_courses'] = 'Não foram localizados módulos para visualização.<BR>Verifique com o responsável pelo curso se a grade curricular foi corretamente configurada.';
$string['no_students_in_group'] = 'Não foram localizados estudantes no grupo selecionado.';

$string['idpessoa_unknown'] = '21-Idpessoa unknown';
$string['connection_fail'] = '22-Connection to SCCP has failed';
$string['add_user_fail'] = '23-An error occured when adding user';
$string['add_edition_fail'] = '24-An error occured when adding edition';

$string['menu_title'] = 'Relatórios de Acompanhamento';
$string['inscricoes:configure_activity'] = 'Configura atividade';
$string['inscricoes:configure_report'] = 'Grade curricular';
$string['inscricoes:see_progress'] = 'Relatório de progresso';
$string['inscricoes:see_completion'] = 'Relatório de conclusão';
$string['inscricoes:send_grades'] = 'Envia resultados';

$string['report_completion'] = 'Relatório de acompanhamento de conclusão de curso';
$string['report_progress'] = 'Relatório de acompanhamento do progresso em módulos';
$string['report_progress_help'] = 'Este relatório reúne dados dos estudantes sobre o andamento e sobre as notas
    de diversas atividades e módulos de um curso. Alterações de configuração devem ser
    feitas nos módulos correspondentes, conforme indicado abaixo:
    <UL>
    <LI>Os módulos listados são aqueles registrados como obrigatórios ou optativos na configuração do "Relatório
        de Acompanhamento" da categoria</LI>
    <LI>As atividades listadas em cada módulo são aquelas registradas como condição para conclusão do módulo
        (ver item "Conclusão de curso" na caixa de "Administração" do módulo)</LI>
    <LI>Para cada atividade é apresentado:
        <UL>
        <LI>a nota obtida pelo estudante caso esteja configurado valor superior a zero para a opção "Nota para aprovação"
        do item de notas correspondente à atividade (ver item "Notas" do menu de "Administração do curso").</LI>
        <LI>caso contrário é apresentado um ícone que indica o andamento/conclusão da atividade.</LI>
        </UL>
    </UL>
    Para ter acesso a este relatório o usuário precisa ter a permissão \'local/inscricoes:see_progress\' na categoria correspondente.
    Os grupos de estudantes aos quais o usuário tem acesso são aqueles nos quais o usuário está inscrito.
    Ele terá acesso a todos os grupos caso tenha a permissão \'moodle/site:accessallgroups\' na categoria.';

$string['coursename'] = 'Nome do módulo';
$string['type'] = 'Tipo';
$string['workload'] = 'Carga Horária (h)';
$string['dependency'] = 'Pré-requisito p/ inscrição';
$string['inscribeperiodo'] = 'Périodo de inscrições';
$string['configurations'] = 'Configurações';
$string['tutor'] = 'Tutor';
$string['tutors'] = 'Tutores';

$string['errors'] = 'Há valores inválidos para os campos indicados abaixo';
$string['invalid_workload'] = 'Carga horária deve estar no intervalo [0..360]';
$string['dependecy_not_opt_dem'] = 'Pré-requisito deve ser um módulo obrigatório ou optativo';
$string['end_before_start'] = 'Data de final de inscrições é anterior à de início';

$string['configure_courses'] = 'Configuração dos módulos';
$string['studentrole'] = 'Papel de estudantes: ';
$string['minoptionalcourses'] = 'Número mínimo de módulos optativos: ';
$string['maxoptionalcourses'] = 'Número máximo de módulos optativos: ';
$string['optionalatonetime'] = 'Selecionar módulos optativos em bloco: ';
$string['tutornotesassign'] = 'Tarefa para anotações dos tutores: ';

$string['mandatory'] = 'obrigatório';
$string['optional'] = 'optativo';
$string['ignore'] = 'não considerar';
$string['not_classified'] = 'não classificado';

$string['externalactivityid'] = 'Id da atividade';
$string['externalactivityid_invalid'] = 'Id da atividade deve ser um número inteiro positivo';
$string['externalactivityid_exists'] = 'Este Id da atividade já está associado a outra categoria';
$string['createcohortbyedition'] = 'Criar cohort por edição';

$string['not_activity_enable'] = 'Não há inscrição habilitada para este contexto.';
$string['already_have_activity'] = 'Não é possível associar esta categoria a um atividade do Sistema de Inscrições
    uma vez que já há associação de atividade com outra categoria acima ou abaixo desta na hierarquia, conforme listado a seguir:';
$string['inconsistency'] = 'Há inconsistência na configuração de inscrição na categoria \'{$a}\' que precisa ser corrigida pelo administrador do Moodle';

$string['already_have_report'] = 'Não é possível configurar relatório nesta categoria visto já haver outra configuração na categoria abaixo:';
?>
