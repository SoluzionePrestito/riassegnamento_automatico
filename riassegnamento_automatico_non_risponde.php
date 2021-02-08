<?php 
    $dieci_giorni_fa = date("Y-m-d", strtotime("-10 days"));
    $lte ='lte';
    
    //SETOPT CURL
    $curl = curl_init();
    my_curl_setopt($curl);
    $response = curl_exec($curl);

    $json = json_decode($response,true);
    $access_token =  "Bearer " .$json['access_token'];

    // $query = construct_query();

    //QUERY SU OPPORTUNITY PER PRATICHE NON RISPONDE CON MODIFICA INFEIRORE A 7 GG
    my_curl_setopt_query($curl,$access_token,$query="{\n\t\"where\": {\n\t\t\"laststato_datamodifica\": {\"$$lte\":\"$dieci_giorni_fa T00:00:00+01:00\"},\n\t\t\"laststato\": \"NON RISPONDE\"},\n\t\"limit\": 2000,\n\t\"skip\": 0\n}");
    $response = curl_exec($curl);
    $json_search_query1 = json_decode($response,true);
    // var_dump($json_search_query1);
    $count_query1 =  count($json_search_query1['result']);

    //QUERY SU OPPORTUNITY PER PRATICHE NUOVO CONTATTO
    my_curl_setopt_query($curl,$access_token,$query="{\n\t\"where\": {\n\t\t\"laststato\": \"NUOVO CONTATTO\"},\n\t\"limit\": 2000,\n\t\"skip\": 0\n}");
    $response = curl_exec($curl);
    $json_search_query2 = json_decode($response,true);
    // var_dump($json_search_query2);
    $count_query2 =  count($json_search_query2['result']);


    $elenco_pratiche_da_riassegnare=[];
    for ($i=0; $i < $count_query1; $i++) { 
        $elenco_pratiche_da_riassegnare []= $json_search_query1['result'][$i]['praticaID'];
    }
    echo 'ELENCO PRATICHE:';
    var_dump($elenco_pratiche_da_riassegnare);

   //CREAZIONE ARRAY AGENTI CON PRATICHE AFFIDATE AD ESSI
    for ($i=0; $i < $count_query1; $i++) { 
        $elenco_agenti_disponibili[$i]= $json_search_query1['result'][$i]['agente'];
    }
    $count_array1 = count($elenco_agenti_disponibili) +1;

    for ($i=0; $i < $count_query2; $i++) { 
        $elenco_agenti_disponibili[$count_array1]= $json_search_query2['result'][$i]['agente'];
        $count_array1++;
    }
    // echo 'ELENCO AGENTI DISPONIBILI';
    // var_dump($elenco_agenti_disponibili);


    //MYSQL FOR TAKE DATA

    //connesione db
    $servername = "34.82.67.217";
    $username_db = "SolPrest";
    $password_db = "SolPrest2020!";
    $database = "EGG_DUMP";

    $conn = new mysqli($servername, $username_db, $password_db, $database);

    // VERIFICA DELLA CONNESSIONE
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sqlQuery = 'SELECT * FROM EGG_DUMP.INFO_VENDITORI';
    $result = $conn->query($sqlQuery);
    $elenco_totale_agenti=[];
    if ($result->num_rows > 0) {
        // output data of each row
        $i=0;
        while($row = $result->fetch_assoc()) {
            $elenco_totale_agenti[$i]["nomevisualizzato"] = $row["nomevisualizzato"];
            $elenco_totale_agenti[$i]["agenteID"] = $row["agenteID"];
            $elenco_totale_agenti[$i]["email1"] = $row["email1"];
            $elenco_totale_agenti[$i]["sedeID"] = $row["sedeID"];
            $elenco_totale_agenti[$i]["team_id"] = $row["team_id"];
            $elenco_totale_agenti[$i]["manager_id"] = $row["manager_id"];
            $elenco_totale_agenti[$i]["nomesede"] = $row["nomesede"];
            $i++;
        }
      } else {
        echo "0 results";
      }
    // var_dump($elenco_totale_agenti);

    // VERIFICHIAMO CHI NON è PRESENTE IN $totale_pratiche_per_agente
    for ($i=0; $i < count($elenco_agenti_disponibili) && $i != count($elenco_totale_agenti); $i++) { 
        if(in_array($elenco_totale_agenti[$i]["nomevisualizzato"], $elenco_agenti_disponibili)){
        }else{
             $elenco_agenti_disponibili[] = $elenco_totale_agenti[$i]["nomevisualizzato"];
        }
    }

    // var_dump($elenco_agenti_disponibili);

    //CREAZIONE ARRAY AGENTI CON COUNT PER OGNI PRATICA
    $totale_pratiche_per_agente= array_count_values($elenco_agenti_disponibili);
    // asort($totale_pratiche_per_agente);
    // $nome_prima_posizione = key($totale_pratiche_per_agente);
    echo 'ELENCO INIZIALE: ' . '<br>';
    var_dump($totale_pratiche_per_agente);

    for ($i=0; $i < count($elenco_pratiche_da_riassegnare); $i++) { 
        asort($totale_pratiche_per_agente);
        // var_dump($totale_pratiche_per_agente);
        $nome_prima_posizione = key($totale_pratiche_per_agente);
        for ($y=0; $y < count($elenco_totale_agenti) ; $y++) { 
            if($elenco_totale_agenti[$y]['nomevisualizzato'] == $nome_prima_posizione){
                $agenteId = $elenco_totale_agenti[$y]['agenteID'];
                $email1 = $elenco_totale_agenti[$y]['email1'];
                $nomesede = $elenco_totale_agenti[$y]['nomesede'];
                $manager_id = $elenco_totale_agenti[$y]['manager_id'];
                echo 'Commerciale ID: ' . $agenteId . '. ' . 'Mail: ' . $email1  . '. ' . 'Sede: ' . $nomesede . '. ' . 'Manager: '. $manager_id . ' Pratica affidata: '. $elenco_pratiche_da_riassegnare[$i] . '<br>';

                my_curl_setopt_update($curl,$access_token,$elenco_pratiche_da_riassegnare[$i],$agenteId,$nomesede,$manager_id);
                // echo emailSide($email1,$elenco_pratiche_da_riassegnare[$i]) . '<br>' . '<br>';
            }
        }
        $totale_pratiche_per_agente[$nome_prima_posizione] = $totale_pratiche_per_agente[$nome_prima_posizione]+1;
    }
    
    asort($totale_pratiche_per_agente);
    echo 'ELENCO FINALE ORDINATO: ' . '<br>';
    var_dump($totale_pratiche_per_agente);
    
 


 







    //FUNCTIONS

    function my_curl_setopt($curl){
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://finance.blackbird71.com/api/v2/oauth/token?grant_type=password&client_id=capital24.marketing.131824e6-0197-11eb-97a7-00505696036a&client_secret=131824fb-0197-11eb-97a7-00505696036a&username=marketing&password=Prostatite2021",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
    }

    //QUERY SU OPPRTUNITY PER PRATICHE NON RISPONDE CON MODIFICA INFEIRORE A 7 GG
    function my_curl_setopt_query($curl,$access_token, $query){
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://finance.blackbird71.com/api/v2/data/entity/opportunity/query",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 50,
            CURLOPT_TIMEOUT => 5000,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: $access_token"
            ),
        ));
    }
    
     //UPDATE SU EGG CON NUOVI DATI AGENTE
     function my_curl_setopt_update($curl,$access_token,$pratica_id,$agente,$sede,$team_manager_id){
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://finance.blackbird71.com/api/v2/data/entity/opportunity/record/$pratica_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS =>"{\t\n\t\"agenteIDattuale\": \"$agente\",\n\t\"sede\": \"$sede\",\n\t \"teammanagerID\": \"$team_manager_id\",\n\t\"laststato\": \"RIASSEGNATA\"\n}",
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: $access_token"
            ),
          ));
          echo $response = curl_exec($curl);
    }

    function emailSide($to ,$praticaId){
        $subject = 'Nuova pratica riassegnata';
        $message = '<html>
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body>';
        $message .= " <br> Ciao, ti è stata riassegnata la pratica numero: " . $praticaId ."</br>";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: <ufficiomarketing@soluzioneprestito.it>" . "\r\n";

        // mail($to, $subject, $message, $headers);
        return 'Mail: ' . $message . ' To: ' . $to;
    }

    function construct_query($time,$condizione){
        $query="{\n\t\"where\": {\n\t\t\"laststato_datamodifica\": {\"$$lte\":\"$time T00:00:00+01:00\"},\n\t\t\"laststato\": \"$condizione\"},\n\t\"limit\": 50,\n\t\"skip\": 0\n}";
    }
?>

<!-- //lastmodifica settimana scorsa -->
<!-- CURLOPT_POSTFIELDS => "{\n\t\"where\": {\n\t\t\"laststato_datamodifica\": \"25-01-2021\"},\n\t\"limit\": 500,\n\t\"skip\": 0\n}", -->

<!-- //laststato non risponde -->
<!-- CURLOPT_POSTFIELDS => "{\n\t\"where\": {\n\t\t\"laststato\": \"NON RISPONDE\"},\n\t\"limit\": 500,\n\t\"skip\": 0\n}", -->

<!-- laststato non risponde + datamod < lastweek -->
<!-- CURLOPT_POSTFIELDS => "{\n\t\"where\": {\n\t\t\"laststato_datamodifica\": {\"$$gte\":\"$previous_week T00:00:00+01:00\"},\n\t\t\"laststato\": \"NON RISPONDE\"},\n\t\"limit\": 50,\n\t\"skip\": 0\n}", -->