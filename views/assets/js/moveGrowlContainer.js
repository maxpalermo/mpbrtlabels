function moveGrowlContainer(parent = null) {
    let growlContainer = document.getElementById("growls-default");

    if (growlContainer) {
        growlContainer.remove();
        growlContainer = null;
    }

    if (!growlContainer) {
        growlContainer = document.createElement("div");
        growlContainer.id = "growls-default";
    }

    if (parent) {
        parent.appendChild(growlContainer);
    } else {
        document.body.appendChild(growlContainer);
    }

    return growlContainer;
}
