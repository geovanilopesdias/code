<?php

require_once (__DIR__.'/../controllers/people_dao.php');
require_once (__DIR__.'/../controllers/book_dao.php');
require_once (__DIR__.'/security_mng.php');

final class InterfaceManager{
    const PAGE_TYPE = [
        'index',
        'login',
        'menu',
        'register',
        'manager',
        'searching',
        'element_detail',
        'result_list'
    ];
    
    private static function is_page_type_valid($page_type): bool{
        return in_array($page_type, self::PAGE_TYPE, true);
    }

    // String transformers:
    public static function mask_phone(string $phone): string{
        return sprintf('(%s) %s.%s', 
            substr($phone, 0, 2),
            substr($phone, 2, 5),
            substr($phone, 7)
        );
    }

    public static function mask_timestamp(string $timestampz): string{
        try {
            $datetime = new DateTime($timestampz);
            return $datetime->format('d/m/y | H:i');
        }
        catch (DateMalformedStringException) {
            return 'Data mal-formado';
        }
    }

    public static function translate_book_status(string $status): string{
        return match ($status){
            'available' => 'Disponível',
            'loaned' => 'Emprestado',
            'reserved' => 'Reservado',
            'damaged' => 'Avariado',
            'lost' => 'Extraviado',
        };
    }

    // Echoers:
    public static function echo_html_head(string $title, string $page_type){
        if (!self::is_page_type_valid($page_type))
            throw new Exception('Page type should be one of the following: '.
                implode(',', self::PAGE_TYPE));

        $base_sheet_path = "../../views/stylesheets/basesheet.css";
        $stylesheet_path = "../../views/stylesheets/$page_type.css";
        $script_path = "../../views/scripts/$page_type.js";
        
        echo "
            <!DOCTYPE html>
                <html lang='pt-br'>
                    <head>
                        <title>$title</title>
                        <link href='$base_sheet_path' rel='stylesheet' page_type='text/css' />
                        <link href='$stylesheet_path' rel='stylesheet' page_type='text/css' />
                        <script src='$script_path' type='module'></script>
                        <link rel='preconnect' href='https://fonts.googleapis.com' />
                        <meta charset='utf-8'>
                    </head>
                    <body>
        ";
    }

    public static function echo_html_tail(): void{
        echo "
                <footer>
                    GABi | Desenvolvido por Geovani L. Dias
                </footer>
                </body>
            </html>
        ";
    }

    // ----- Special tags:
    public static function system_logo(string $page_type): string{
        if (!self::is_page_type_valid($page_type))
            throw new Exception('Page type should be one of the following: '.
                implode(',', self::PAGE_TYPE));
        
        $logo_path = "../../views/images/gabi_logo.png";
        return "<img id='gabi_logo_$page_type' class='gabi_logo' src='$logo_path'/></br>";
    }
    
    public static function menu_greetings(string $user_name): string{
        $today = new DateTime("now", new DateTimeZone("America/Sao_Paulo"));
        $today = $today -> format('d/m/y');
        return "
            <h1>Olá, $user_name!<h1>
            <h2><em>Hoje é $today</em></h2>
        ";
    }

    // ----- Buttons
    public static function logout_button(): string{
        return "
            <form method='post' action='logout.php'>
                <input 
                id='logout_button' 
                class='back_buttons'
                type='submit' 
                value='&#x25c0; | SAIR'>
            </form>
        ";
    }

    public static function back_to_menu_button(): string{
        return "
            <form method='post' action='".
                htmlspecialchars($_SESSION['user_role'])."_menu.php'>
                <input 
                    id='back_to_menu_button' 
                    class='back_buttons'
                    type='submit' 
                    value='&#x25c0; | MENU'>
            </form>
        ";
    }

    public static function back_to_search_button(string $search_type): string{
        $search_page_suffix = ($_SESSION['user_role'] == 'student') ?
            '_readers' : '';
        return "
            <form method='post' action='".$search_type."_search$search_page_suffix.php'>
                <input 
                    id='back_to_search_button' 
                    class='back_buttons'
                    type='submit' 
                    value='&#x25c0; | NOVA BUSCA'>
            </form>
        ";
    }

    private static function close_loan_button(int $loan_id): string{
        return "
            <form method='post' action='loan_update_manager.php'>
                <input type='date' name='return_date' required>
                <input type='hidden' name='action' value='close'>
                <input type='hidden' name='id' value='".$loan_id."'>
                <input 
                    id='close_loan_button' 
                    class='back_buttons'
                    type='submit' 
                    value='&crarr; | DEVOLVER'>
            </form>
        ";
    }

    private static function renovate_loan_button(int $loan_id): string{
        return "
            <form method='post' action='loan_update_manager.php'>
                <input type='date' name='renovation_date' required>
                <input type='hidden' name='action' value='renovate'>
                <input type='hidden' name='id' value='".$loan_id."'>
                <input 
                    id='renovate_loan_button' 
                    class='back_buttons'
                    type='submit' 
                    value='&#10022; | RENOVAR'>
            </form>
        ";
    }

    public static function loan_button_grid(int $loan_id, $errors): string{
        if (session_status() !== PHP_SESSION_ACTIVE)
            {session_start();}

        $id_from_session = isset($_SESSION['form_data']['id']) ?
            intval($_SESSION['form_data']['id']) 
            : null;
            
        if (SecurityManager::can_user_register($_SESSION['user_id'])) {
            return "
                <div id='loan_button_grid'>".
                    ((!empty($errors)) ? self::search_input_disclaimer($errors['invalid_date'] ?? '') : '') .
                    (is_null($id_from_session) ? self::close_loan_button($loan_id) : self::close_loan_button($id_from_session)). 
                    ((!empty($errors)) ? self::search_input_disclaimer($errors['invalid_closing'] ?? '') : '') .
                    (is_null($id_from_session) ? self::renovate_loan_button($loan_id) : self::renovate_loan_button($id_from_session)).
                    ((!empty($errors)) ? self::search_input_disclaimer($errors['invalid_renovation'] ?? '') : '') ."
                </div>
            ";
        }

        else {return "";}
            
    }

    protected static function update_element_button(string $element_type, mixed $element): string{
        if(SecurityManager::is_updating_permited($element_type)) {
            return "
                <form method='post' action='".$element_type."_updater.php'>
                    <input type='hidden' name='id' value='".$element -> get_id()."'>
                    <input 
                        id='update_element_button' 
                        class='back_buttons'
                        type='submit' 
                        value='&#9998; | MODIFICAR'>
                </form>
            ";
        }

        else return "";
    }

    /**
     * Returns a post-form to deleter.php only if the element_type is permited from
     * SecurityManager validation method. Otherwise, it returns an empty string.
     */
    protected static function delete_element_button(string $element_type, mixed $element): string{
        if(SecurityManager::is_deletion_permited($element_type)) {
            return "
                <form method='post' action='delete_manager.php'>
                    <input type='hidden' name='id' value='".$element -> get_id()."'>
                    <input type='hidden' name='element_type' value='$element_type'>
                    <input type='hidden' name='deletion_step' value='confirmation'>
                    <input 
                        id='delete_element_button' 
                        class='back_buttons'
                        type='submit' 
                        value='&#128465; | EXCLUIR'>
                </form>
            ";
        }
        
        else return "";
    }



    /**
     * @var $register_type: Should be rather simply user, opus (etc.) or loan.
     */
    public static function back_to_register_button(string $register_type): string{
        return "
            <form method='post' action='".$register_type."_register.php'>
                <input 
                    id='back_to_register_button' 
                    class='back_buttons'
                    type='submit' 
                    value='&#x25c0; | NOVO CADASTRO'>
            </form>
        ";
    }

    public static function menu_update_delete_button_grid(string $element_type, mixed $element): string{
        if (session_status() !== PHP_SESSION_ACTIVE)
            {session_start();}

        if (SecurityManager::can_user_register($_SESSION['user_id'])) {
            return "
                <div id='menu_update_delete_button_grid'>".
                    self::back_to_menu_button($element_type).
                    self::update_element_button($element_type, $element).
                    self::delete_element_button($element_type, $element). "
                </div>
            ";
        }

        else {
            return "
                <div id='menu_update_delete_button_grid'>".
                    self::back_to_menu_button($element_type). "
                </div>
            ";
        }
            
    }
    
    // ----- For forms
    public static function search_button(): string {
        return "<input class='search_button' type='submit' value='&#x1F50D;'>";
    }

    public static function register_button(): string {
        return "<input class='register_button' type='submit' value='Cadastrar &#x1F4BE;'>";
    }

    public static function updater_button(): string {
        return "<input class='register_button' type='submit' value='Atualizar &#x1F4BE;'>";
    }
    
    public static function search_input_disclaimer($disclaimer): string{
        return "<p class='search_disclaimer'>".
            htmlspecialchars($disclaimer ?? '')."</p></br>";
    }

    public static function error_input_disclaimer($disclaimer): string{
        return "<p class='error_disclaimer'>".
            htmlspecialchars($disclaimer ?? '')."</p></br>";
    }

    /**
     * Ajustar para retornar à respectiva página de busca
     * com a mensagem acima da div de busca.
     */
    public static function no_results_disclaimer($disclaimer): string{
        return "<div class='no_results_disclaimer'><p>".
            htmlspecialchars($disclaimer ?? '')."</p></br></div>";
    }

    /**
     * Returns a serie of input-labels.
     * 
     * @param array $selector: An 2D array containing more than two inner associative arrays, which
     * contains the data to the input construction: the inner array's keys are named after the 'value' and 'id'
     * properties needed for the tag; the inner array's values refer to the content of their labels.
     * Example: $selector = [['id' => 'male', 'content' => 'Masculino], ...
     * @param string $group_name: The 'name' property of all input tags that'll groups them.
     * @param string $fieldset_legend: Value of the legend of the fieldset nesting the input-radio group.
     * @param ?string $div_name: Optional name for a div to stylize it in css. Letting it null
     * won't nest the input-radio group in any div tags.
     * @param bool $first_option_checked: if true, inserts the 'checked' property in the first option.
     */
    public static function input_radio_group(
        array $selector, string $group_name, string $fieldset_legend,
        ?string $div_name = null, bool $first_option_checked = true): string {
            if (count($selector) < 1) throw new InvalidArgumentException('More than one option is needed');
            
            $radio_group = '';
            for ($i = 0; $i < count($selector); $i++){
                if (!isset($selector[$i]['id'], $selector[$i]['content'])) 
                    throw new InvalidArgumentException('Each option must have "id" and "content" keys.');
                $id = htmlspecialchars(trim($selector[$i]['id']));
                $content = htmlspecialchars(trim($selector[$i]['content']));
                $group = htmlspecialchars($group_name);
                $optional_check = ($i == 0 and $first_option_checked) ? 'checked' : '';
                $radio_group .= "
                    <input type='radio' id='$id' name='$group' value='$id' $optional_check/>
                    <label for='$id'>$content</label>
                ";
            }
            return $div_name ?
                "<div id='".htmlspecialchars($div_name ?? '')."'>
                    <fieldset>
                        <legend>".htmlspecialchars($fieldset_legend ?? '')."</legend>
                        $radio_group
                    </fieldset>
                </div>" :

                "<fieldset>
                    <legend>".htmlspecialchars($fieldset_legend ?? '')."</legend>
                    $radio_group
                </fieldset>";
    }

    public static function input_checkbox_group(string $group_name, array $id_label_pairs, string $fieldset_legend = '') : string {
        $html = (empty($fieldset_label)) ? "" :
            "<fieldset><legend>$fieldset_legend</legend>";
        foreach ($id_label_pairs as $id => $label) 
            $html .= "
                <input type='checkbox' id='$id' value='$id' name='$group_name'>
                <label for='$id'>$label</label>
            ";
        
        $html .= (empty($fieldset_label)) ? "" :
            "</fieldset>";
        return $html;
    }

    public static function input_checkbox_single(string $id_name, string $label, string $fieldset_legend = '', bool $checked = true) : string {
        $html = (empty($fieldset_label)) ? "" :
            "<fieldset><legend>$fieldset_legend</legend>";
        $html .= "
                <input type='checkbox' id='$id_name' name='$id_name' ". 
                (($checked) ? 'checked' : '').">
                <label for='$id_name'>$label</label>
            ";
        
        $html .= (empty($fieldset_label)) ? "" :
            "</fieldset>";

        return $html;
    }

    /**
     * Selector tag with all the registered classrooms.
     * 
     * The values of the options are the respective classrooms' ids
     *  to properly fetch them.
     */
    public static function classroom_selector(bool $is_required = true): string{
        $classroom_intances = PeopleDAO::fetch_all_classrooms();
        $required = ($is_required) ? 'required' : '';
        $selector = "
            <select name='classrooms_ids[]' class='selector' $required multiple size='3'>
                <option value=''>--- Seleciona uma ou mais turmas</option>";
        foreach ($classroom_intances as $c)
            $selector .= "<option value='".$c->get_id()."'>".
                              $c->get_name().'/'.$c->get_year()."
                          </option>";
        
        return "$selector</select>";
    }

    public static function reader_selector(): string{
        $reader_intances = PeopleDAO::fetch_all_readers();
        $selector = "
            <select name='loaner_id' class='selector' required>
                <option value=''>--- Seleciona um leitor</option>";
        foreach ($reader_intances as $r){
            $selector .= "<option value='".$r->get_id()."'>". ucwords($r->get_name());    
            if ($r -> get_role() == 'student') {
                $classrooms = PeopleDAO::fetch_student_classrooms($r -> get_id());
                if (is_array($classrooms))
                    {$classrooms = implode(', ', $classrooms);}
                $selector .= " (".$classrooms.")";
            }
            $selector .= "</option>";
        }
        
        return "$selector</select>";
    }

    

    /**
     * Selector tag with all the registered writers.
     * 
     * The values of the options are the respective writers' ids
     *  to properly fetch them.
     */
    public static function writer_selector(bool $is_required = true): string{
        $writer_intances = BookDAO::fetch_all_writers();
        $required = ($is_required) ? 'required' : '';
        if(!empty($writer_intances)){
            $selector = "
                <select name='writer_ids[]' class='selector' $required multiple size='3'>
                    <option value=''>--- Seleciona um ou mais autores</option>";
            foreach ($writer_intances as $w)
                $selector .= "<option value='".$w->get_id()."'>".
                        $w->get_name().
                        (($w->get_birth_year() > 0) ? ' ('.$w->get_birth_year().')' : '').
                    "</option>";
        
            return "$selector</select><br>";
        }

        else {
            return self::error_input_disclaimer('
                Não há autores cadastrados! Cadastre um primeiro.
            ');
        }

    }

    public static function opus_selector(bool $is_required = true): string{
        $opus_intances = BookDAO::fetch_all_opuses();
        $required = ($is_required) ? 'required' : '';
        $selector = "
            <select name='opus_id' class='selector' $required>
                <option value=''>--- Seleciona uma obra</option>";
        foreach ($opus_intances as $o)
            $selector .= "<option value='".$o->get_id()."'>".
                              $o->get_title().
                              (!is_null($o->get_original_year()) ? ' ('.$o->get_original_year().')' : '').
                              "</option>";
        
        return "$selector</select><br>";
    }

    public static function edition_selector(bool $is_required = true): string{
        $editions = BookDAO::fetch_all_editions_essentially_with_opus_title();
        $required = ($is_required) ? 'required' : '';
        $selector = "
            <select name='edition_id' class='selector' $required>
                <option value=''>--- Seleciona uma edição</option>";
            foreach ($editions as $e)
            $selector .= "<option value='".$e['id']."'>".
                              $e['title'].
                              (!is_null($e['publisher']) ? ' | Ed.: '.$e['publisher'] : '').
                              (!is_null($e['collection']) ? ' | Col.: '.$e['collection'] : '').
                              (!is_null($e['isbn']) ? ' | ISBN: '.$e['isbn'] : '').
                              (!is_null($e['year']) ? ' | '.$e['year'] : '').
                              "</option>";
        
        return "$selector</select><br>";
    }

    public static function publisher_selector(): string{
        $pub_intances = BookDAO::fetch_all_publishers();
        if(!empty($pub_intances)) {
            $selector = "
            <select name='publisher_id' class='selector'>
                <option value=''>--- Seleciona uma editora</option>";
            foreach ($pub_intances as $p)
                $selector .= "<option value='".$p->get_id()."'>".
                                $p->get_name()."</option>";
            
            return "$selector</select><br>";
        }

        else {
            return self::error_input_disclaimer('
                Não há editoras cadastradas! Cadastre uma primeiro.
            ');
        }
        
    }

    public static function collection_selector(): string{
        $coll_intances = BookDAO::fetch_all_collections();
        if(!empty($coll_intances)) {
            $selector = "
                <select name='collection_id' class='selector'>
                    <option value=''>--- Seleciona uma coleção</option>";
            foreach ($coll_intances as $c)
                $selector .= "<option value='".$c->get_id()."'>".
                                $c->get_name()."</option>";
            
            return "$selector</select><br>";
        }

        else {
            return self::error_input_disclaimer('
                Não há coleções cadastradas! Cadastre uma primeiro.
            ');
        }
    }

    /**
     * Generates an HTML table string from a set of results.
     *
     * @param string $caption The caption for the table.
     * @param array $results An array of associative arrays containing the data to populate the table.
     * @return string The HTML string for the table.
     * @throws InvalidArgumentException If $results is empty or not formatted correctly.
     */
    public static function table_of_results(string $data_type, string $caption, array $results): string {
        if (empty($results)) 
            throw new InvalidArgumentException('The results array must not be empty.');
        
        if ($data_type === 'classroom') {$data_type = 'user';}

        foreach ($results as $row) 
            if (!is_array($row) || array_values($row) === $row) 
                throw new InvalidArgumentException('Each result row must be an associative array.');
        
        // Caption and header
        $headers = array_keys($results[0]);
        $table = "<div class='results'><table class='sortable'>\n<caption>" . htmlspecialchars($caption) . "</caption>\n<thead>\n<tr>";
        foreach ($headers as $header) {
            $table .= ($header === 'id') ?
                "<th>Ver</th>" : "<th>" . ucfirst(htmlspecialchars($header)) . "</th>";
        }
        $table .= "</tr>\n</thead>\n<tbody>";

        // Rows
        $tr_class = 'odd';
        foreach ($results as $row) {
            $table .= "\n<tr class='$tr_class'>";
            foreach ($headers as $header) $table .= match($header){
                //General
                'id' => "<td><form method='post' action='".$data_type."_element_detail.php'>
                    <input type='hidden' name='id' value='".$row[$header]."'>
                    <input type='submit' class='element_detail_link' value='&#128065;'></form></td>", 
                    
                // Readers
                'telefone' => "<td>" . self::mask_phone(htmlspecialchars($row[$header] ?? '')) . "</td>",
                'nome' => "<td>" . ucwords(htmlspecialchars($row[$header] ?? '')) . "</td>",
                'tipo' => ($row[$header] === 'student') ? "<td>Discente</td>" : "<td>Docente</td>",
                'último acesso', 'retirada', 'devolução' => "<td>" . self::mask_timestamp(htmlspecialchars($row[$header])) . "</td>",
                'dívida' => "<td>R$ " . number_format(trim(htmlspecialchars($row[$header])), 2, ',', '.') . "</td>",

                // Books
                'título' => "<td>" . htmlspecialchars($row[$header]) . "</td>",
                
                'autores' => "<td>" . implode(', ', array_map(function($author) {
                    return htmlspecialchars($author['name']) . " (" . htmlspecialchars($author['birth_year'] ?? '') . ")";},
                    json_decode($row[$header], true))) . "</td>",
                'situação' => "<td>" . self::translate_book_status(htmlspecialchars($row[$header] ?? '')) . "</td>",
                
'weblink' => "<td><a id='opus_weblink' target='blank' href='" . htmlspecialchars($row[$header] ?? '') . "'>&#128279;</a></td>",
                default => "<td>" . ucfirst(htmlspecialchars($row[$header] ?? '')) . "</td>"
            };
            $table .= "</tr>";
            $tr_class = ($tr_class === 'odd') ? 'even' : 'odd';
        }

        $table .= "\n</tbody>\n</table></div>";

        return $table;
    }

}

// echoPayDebtButton