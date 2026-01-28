function bootstrapTableFixIcons() {
    fixDropDownPagination();
    setBootstrapTableIcons();
}

function fixDropDownPagination() {
    $(".fixed-table-pagination .dropdown-toggle")
        .off("click")
        .on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $btn = $(this);
            const $menu = $btn.closest(".btn-group").find(".dropdown-menu");

            $(".fixed-table-pagination .dropdown-menu").not($menu).removeClass("show");
            $menu.toggleClass("show");
        });

    // Normalizza il markup del dropdown page-size a Bootstrap 3
    $(".fixed-table-pagination .btn-group.dropdown").each(function () {
        var $group = $(this);
        var $menuDiv = $group.find("> .dropdown-menu");

        if ($menuDiv.length) {
            // Se non è già <ul>, converti
            if ($menuDiv.prop("tagName") !== "UL") {
                var $ul = $('<ul class="dropdown-menu" role="menu"></ul>');

                $menuDiv.find("a").each(function () {
                    var $a = $(this);
                    var $li = $("<li></li>");
                    $a.removeClass("dropdown-item"); // classe BS4/5 inutile qui
                    $li.append($a);
                    $ul.append($li);
                });

                $menuDiv.replaceWith($ul);
            }
        }

        // Assicura data-toggle (non data-bs-toggle) e inizializza il plugin
        var $btn = $group.find("> .dropdown-toggle");
        if ($btn.attr("data-bs-toggle") === "dropdown") {
            $btn.removeAttr("data-bs-toggle").attr("data-toggle", "dropdown");
        }
        if (typeof $.fn.dropdown === "function") {
            $btn.dropdown();
        }
    });

    $("button[name=filterControlSwitch]").html("<i class='material-icons'>filter_list</i>");

    $(document)
        .off("click.bs-table-page-size")
        .on("click.bs-table-page-size", function () {
            $(".fixed-table-pagination .dropdown-menu").removeClass("show");
        });
}

function setBootstrapTableIcons() {
    document.querySelectorAll("button[name=refresh] i").forEach((i) => {
        i.setAttribute("class", "material-icons");
        i.innerHTML = "refresh";
    });

    document.querySelectorAll("button[name=clearSearch] i").forEach((i) => {
        i.setAttribute("class", "material-icons");
        i.innerHTML = "clear";
    });
}

window.bootstrapTableFixIcons = bootstrapTableFixIcons;
