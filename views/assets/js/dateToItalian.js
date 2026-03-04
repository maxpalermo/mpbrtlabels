function dateToItalian(dateISO) {
    if (!dateISO) return "";

    if (dateISO == "0000-00-00" || dateISO == "0000-00-00 00:00:00") {
        return "--";
    }

    const date = new Date(dateISO);
    if (isNaN(date.getTime())) return dateISO;

    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear();

    return `${day}/${month}/${year}`;
}

window.dateToItalian = dateToItalian;
