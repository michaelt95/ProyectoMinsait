$(document).ready(function () {
    $("#contenedor div").on("click", function () {
        switch ($(this).attr("id")) {
            case "CreadorSecciones":
                window.location.href="CreadorSecciones.php";
                break;
            case "Gestion":
                window.history.replaceState(new Date(), null, "Index.php?P=Gestion");
                $("#Principal").load("Gestion.php");
                break;
        }
    });
});