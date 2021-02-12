<?php

    $stati = ['NON%20RISPONDE','NON%20RISPONDE%202','NON%20RISPONDE%203','NON%20RISPONDE%204','NON%20RISPONDE%205','MAI%20REPERIBILE','FORMULATA%20OFFERTA','FEEDBACK%20QUOTA%20CEDIBILE','RICHIESTA%20DOC%20PER%20VERIFICA%20FATTIBILITA','RICHIESTA%20DOC%20PER%20CARICAMENTO'];

    $count = 0;

    for ($i=0; $i < count($stati); $i++) { 
        $stato = $stati[$i];
        $data = date("Y-m-d", strtotime("-10 days"));
        if($stato == 'FORMULATA%20OFFERTA' || $stato == 'FEEDBACK%20QUOTA%20CEDIBILE'){
            $data = date("Y-m-d", strtotime("-15 days"));
        }elseif($stato == 'MAI%20REPERIBILE'){
            $data = date("Y-m-d", strtotime("-1 day"));
        }elseif($stato == 'RICHIESTA%20DOC%20PER%20VERIFICA%20FATTIBILITA' || $stato == 'RICHIESTA%20DOC%20PER%20CARICAMENTO'){
            $data = date("Y-m-d", strtotime("-30 days"));
        }


        echo 'Lancio Script: ' . $stato . ' con Data: ' . $data . '<br>';
        $script1= file_get_contents('http://localhost:8888/riassegnamento_automatico/riassegnamento_automatico.php/?stato=' . $stato . '&data=' . $data . '&count=' . $count);
        if($script1 == true){
            echo 'Success' . '<br>';
            echo $script1;
            sleep(10);
        }
    }
?>
