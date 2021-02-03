<?php 
    echo $_SERVER["SCRIPT_NAME"];
    echo '<br>';
    //CONNESSIONE AL NOSTRO Mysql
    $servername = "34.82.67.217";
    $username_db = "dm_strategy";
    $password_db = "DirectM";
    $database = "EGG_DUMP_SERALE";

    $conn = new mysqli($servername, $username_db, $password_db, $database);

    // VERIFICA DELLA CONNESSIONE
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }

    $previous_week = date("Y-m-d", strtotime("last week monday"));
    $lte ='lte';
    
    //SETOPT CURL
    $curl = curl_init();
    my_curl_setopt($curl);
    $response = curl_exec($curl);

    $json = json_decode($response,true);
    $access_token =  "Bearer " .$json['access_token'];

    //QUERY SU OPPORTUNITY PER PRATICHE NON RISPONDE CON MODIFICA INFEIRORE A 7 GG
    my_curl_setopt_query($curl,$access_token,$query="{\n\t\"where\": {\n\t\t\"laststato_datamodifica\": {\"$$lte\":\"$previous_week T00:00:00+01:00\"},\n\t\t\"laststato\": \"NON RISPONDE\"},\n\t\"limit\": 50,\n\t\"skip\": 0\n}");
    $response = curl_exec($curl);
    $json_search_query1 = json_decode($response,true);
    // var_dump($json_search_query1);
    $count_query1 =  count($json_search_query1['result']);

    //QUERY SU OPPORTUNITY PER PRATICHE NUOVO CONTATTO
    my_curl_setopt_query($curl,$access_token,$query="{\n\t\"where\": {\n\t\t\"laststato\": \"NUOVO CONTATTO\"},\n\t\"limit\": 50,\n\t\"skip\": 0\n}");
    $response = curl_exec($curl);
    $json_search_query2 = json_decode($response,true);
    // var_dump($json_search_query2);
    $count_query2 =  count($json_search_query2['result']);


    $elenco_pratiche_da_riassegnare=[];
    for ($i=0; $i < $count_query1; $i++) { 
        $elenco_pratiche_da_riassegnare []= $json_search_query1['result'][$i]['praticaID'];
        $elenco_pratiche_da_riassegnare []= $json_search_query1['result'][$i]['sede'];
    }
    // var_dump($elenco_pratiche_da_riassegnare);

   //CREAZIONE ARRAY AGENTI CON PRATICHE AFFIDATE AD ESSI
    for ($i=0; $i < $count_query1; $i++) { 
        $elenco_agenti_disponibili[$i]['agente']= $json_search_query1['result'][$i]['agente'];
        $elenco_agenti_disponibili[$i]['sede']= $json_search_query1['result'][$i]['sede'];
        $elenco_agenti_disponibili[$i]['teammanager']= $json_search_query1['result'][$i]['teammanager'];
    }
    $count_array1 = count($elenco_agenti_disponibili) +1;

    for ($i=0; $i < $count_query2; $i++) { 
        $elenco_agenti_disponibili[$count_array1]['agente']= $json_search_query2['result'][$i]['agente'];
        $elenco_agenti_disponibili[$count_array1]['sede']= $json_search_query2['result'][$i]['sede'];
        $elenco_agenti_disponibili[$count_array1]['teammanager']= $json_search_query2['result'][$i]['teammanager'];
        $count_array1++;
    }
    var_dump($elenco_agenti_disponibili);


    //CREAZIONE ARRAY AGENTI CON COUNT PER OGNI PRATICA
    $totale_pratiche_per_agente= array_count_values(array_column($elenco_agenti_disponibili, 'agente'));
    asort($totale_pratiche_per_agente);
    var_dump($totale_pratiche_per_agente);

    $conn->close();







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
        //\"percentCompleta\":{\"$$gt\": \"39\"},
    }
?>

<!-- //lastmodifica settimana scorsa -->
<!-- CURLOPT_POSTFIELDS => "{\n\t\"where\": {\n\t\t\"laststato_datamodifica\": \"25-01-2021\"},\n\t\"limit\": 500,\n\t\"skip\": 0\n}", -->

<!-- //laststato non risponde -->
<!-- CURLOPT_POSTFIELDS => "{\n\t\"where\": {\n\t\t\"laststato\": \"NON RISPONDE\"},\n\t\"limit\": 500,\n\t\"skip\": 0\n}", -->

<!-- laststato non risponde + datamod < lastweek -->
<!-- CURLOPT_POSTFIELDS => "{\n\t\"where\": {\n\t\t\"laststato_datamodifica\": {\"$$gte\":\"$previous_week T00:00:00+01:00\"},\n\t\t\"laststato\": \"NON RISPONDE\"},\n\t\"limit\": 50,\n\t\"skip\": 0\n}", -->