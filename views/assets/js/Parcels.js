class Parcels {
    adminControllerUrl;
    orderId;
    parcels = [];

    constructor(adminControllerUrl, orderId) {
        this.adminControllerUrl = adminControllerUrl;
        this.orderId = orderId;
    }

    async fetchParcels() {
        const self = this;
        const formData = new FormData();
        formData.append("action", "fetchParcels");
        formData.append("ajax", 1);
        formData.append("orderId", self.orderId);

        const response = await fetch(`${self.adminControllerUrl}`, {
            method: "POST",
            body: formData,
        });
        const data = await response.json();

        const parcel = new Parcel(self.adminControllerUrl);
        parcel.init(data.parcel.id, data.parcel.x, data.parcel.y, data.parcel.z, data.parcel.weight, data.parcel.volume);

        let addParcel = true;
        self.parcels.forEach((p) => {
            if (p.parcelId === parcel.parcelId) {
                p = parcel;
                addParcel = false;
                console.log("Parcel updated");
            }
        });

        if (addParcel) {
            self.parcels.push(parcel);
            console.log("Parcel added");
        }

        return parcel;
    }

    add(parcel) {
        const self = this;
        self.parcels.push(parcel);
    }

    remove(parcel) {
        const self = this;
        self.parcels = self.parcels.filter((p) => p.parcelId !== parcel.parcelId);
    }

    update(parcel) {
        const self = this;
        self.parcels = self.parcels.map((p) => {
            if (p.parcelId === parcel.parcelId) {
                return parcel;
            }
            return p;
        });
    }

    list() {
        const self = this;
        return self.parcels;
    }
}
