<html>
    <head>
        <link rel="stylesheet" href="css/Gestion.css"/>
        <link rel="stylesheet" href="css/Modal.css"/>
    </head>
    <body>
        <div class="Modal-fondo d-none"></div>
        <div class="Modal-dialogo d-none"></div>
        <h1>Gestion</h1>
        <div id='seleccion'>
            <select id='opcion'>
                <option selected disabled>Elige</option>
                <option value='Secciones'>Secciones</option>
                <option value='AmbitosYvariables'>Ambitos y Variables</option>
                <option value='Documentos'>Documentos</option>
            </select>
            <i></i>
        </div>
        <div id='contenido'>
            <div id="interiorContenido">
            </div>
        </div>
        <script src="js/Modal.js"></script>
        <script src="js/Gestion.js"></script>
    </body>
</html>
