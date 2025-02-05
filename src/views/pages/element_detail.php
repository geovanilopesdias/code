<?php

require_once(__DIR__ . '/../../managers/interface_mng.php');
require_once(__DIR__ . '/../../controllers/people_dao.php');
require_once(__DIR__ . '/../../controllers/book_dao.php');
require_once(__DIR__ . '/../../controllers/loan_dao.php');

abstract class ElementDetail{
    const PAGE_TYPE = 'element_detail';

    protected abstract function detail_element($element): string;
    protected abstract function data_table($element): string;

    protected function get_element(string $element_type): mixed{
        if (isset($_POST['asset_code'])) {
            $asset_code = htmlspecialchars($_POST['asset_code']);
            return BookDAO::fetch_bookcopy_by_asset_code($asset_code);
        }
        else {
            $id = intval(htmlspecialchars(
                $_POST['id'] ??
                $_SESSION['form_data']['id']));
            
            return match($element_type){
                'user' => PeopleDAO::fetch_reader_by_id($id, true),
                'classroom' => PeopleDAO::fetch_classroom_by_id($id),
                'opus' => BookDAO::fetch_opus_by_id($id),
                'edition' => BookDAO::fetch_edition_by_id($id),
                'bookcopy' => BookDAO::fetch_bookcopy_by_id($id),
                'loan' => LoanDAO::fetch_loan_by_id($id),
            };
        }
        
    }

    public function echo_structure(string $element_type){
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) or
            (($element_type === 'user' or $element_type === 'loan') and
                $_SESSION['user_role'] !== 'librarian'))
                    {header('Location: login.php'); exit;}
        
        $title = "GABi | Detalhamento de " .
            match ($element_type){
                'user' => 'Leitor',
                'classroom' => 'Turma',
                'writer' => 'Autor',
                'opus' => 'Obra',
                'edition' => 'Edição',
                'bookcopy', 'book' => 'Exemplar',
                'loan' => 'Empréstimo',
            };
        InterfaceManager::echo_html_head($title, self::PAGE_TYPE);
        echo InterfaceManager::system_logo(self::PAGE_TYPE);
        echo "<div id='element_detail'>";
            echo InterfaceManager::menu_update_delete_button_grid($element_type, self::get_element($element_type));
            echo $this -> detail_element(self::get_element($element_type));
            echo $this -> data_table(self::get_element($element_type));
        echo "</div>";
        InterfaceManager::echo_html_tail();
        exit;
    }

}
      

?>