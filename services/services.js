// TORQUEPOINT - SERVICES JAVASCRIPT

const vehicleType = document.getElementById("vehicleType");
const vehicleMake = document.getElementById("vehicleMake");
const vehicleModel = document.getElementById("vehicleModel");
const vehicleYear = document.getElementById("vehicleYear");
const findBtn = document.getElementById("findBtn");
const serviceResults = document.getElementById("serviceResults");
const checkoutSection = document.getElementById("checkoutSection");

const vehicleDatabase = {
    car: {
        "Toyota": ["Corolla","Prius","Aqua","Vitz","Camry","Axio","Premio","Allion","Yaris"],
        "Nissan": ["Sunny","Wingroad","Leaf","Sylphy","March","Bluebird"],
        "Honda": ["Civic","Fit","Accord","Grace","Insight","City"],
        "Suzuki": ["Alto","Swift","Wagon R","Baleno","K12"],
        "Mazda": ["3","6","Demio","Axela","CX-3"],
        "BMW": ["3 Series","5 Series","7 Series","M3","M5"],
        "Mercedes-Benz": ["A-Class","C-Class","E-Class","S-Class","CLA"],
        "Audi": ["A3","A4","A6","A8","RS3"],
        "Volkswagen": ["Polo","Golf","Passat","Jetta","Beetle"],
        "Mitsubishi": ["Lancer","Mirage","Lancer EX","Galant"],
        "Jaguar": ["XE","XF","XJ","F-Type"]
    },

    van: {
        "Toyota": ["Hiace","KDH","Hiace Grand Cabin","Estima","Voxy","Noah"],
        "Nissan": ["Caravan","Vanette","Elgrand","Serena"],
        "Suzuki": ["Every","APV"],
        "Mitsubishi": ["Grandis","Delica","Outlander"],
        "Mercedes-Benz": ["Sprinter","Vito","V-Class"],
        "Volkswagen": ["Transporter","Caddy","Multivan"]
    },

    suv: {
        "Toyota": ["Fortuner","RAV4","Land Cruiser","Prado","Rush","Harrier"],
        "Honda": ["CR-V","Vezel","HR-V","BR-V","Pilot"],
        "Nissan": ["X-Trail","Juke","Patrol","Terrano"],
        "Mitsubishi": ["Outlander","Pajero","Montero","ASX"],
        "Kia": ["Sportage","Sorento","Seltos"],
        "Hyundai": ["Tucson","Santa Fe","Creta","Palisade"],
        "Land Rover": ["Defender","Range Rover","Discovery","Evoque","Velar"],
        "BMW": ["X1","X3","X5","X6","X7"],
        "Audi": ["Q3","Q5","Q7","Q8"],
        "Jaguar": ["F-Pace","E-Pace","I-Pace"],
        "Jeep": ["Wrangler","Cherokee","Grand Cherokee"]
    },

    motorcycle: {
        "Honda": ["Dio","Hornet","CBR 250","Unicorn","Activa","CB350"],
        "Yamaha": ["FZ","R15","MT-15","Fazer","YZF-R1"],
        "Bajaj": ["Pulsar","Dominar","Discover","Avenger"],
        "Royal Enfield": ["Classic 350","Himalayan","Meteor 350","Interceptor 650"],
        "Suzuki": ["Gixxer","Access","V-Strom","GSX-R"],
        "TVS": ["Apache","Ntorq","Jupiter"],
        "KTM": ["Duke 200","Duke 390","Adventure 390"],
        "Kawasaki": ["Ninja 400","Z900","Versys 650"]
    },

    truck: {
        "Isuzu": ["N-Series","FRR","FTR","Elf"],
        "Tata": ["LPT","Ace","LPK","Xenon"],
        "Mitsubishi": ["Canter","Fighter","FK"],
        "UD": ["Quon","Kuzer","MK"],
        "Ashok Leyland": ["Comet","Husky","Captain"],
        "Mercedes-Benz": ["Atego","Actros","Arocs"]
    },

    bus: {
        "Toyota": ["Coaster","Hiace Commuter"],
        "Mitsubishi": ["Rosa","Aero","Fuso"],
        "Hyundai": ["County","Universe","Solati"],
        "Ashok Leyland": ["Viking","Leopard","Sunbeam"],
        "Isuzu": ["NQR Bus","FRR Bus"]
    }
};

const yearRanges = {
    "Corolla": [1990,2026], "Prius": [1997,2026], "Aqua": [2011,2026],
    "Vitz": [1999,2019], "Camry": [1990,2026], "Axio": [2006,2026],
    "Premio": [2001,2021], "Allion": [2001,2021], "Yaris": [1999,2026],
    "Sunny": [1990,2026], "Wingroad": [1996,2018], "Leaf": [2010,2026],
    "Sylphy": [2000,2026], "March": [1992,2022], "Bluebird": [1990,2012],
    "Civic": [1990,2026], "Fit": [2001,2026], "Accord": [1990,2026],
    "Grace": [2014,2020], "Insight": [1999,2022], "City": [1996,2026],
    "Alto": [1990,2026], "Swift": [2004,2026], "Wagon R": [1993,2026],
    "Baleno": [1995,2026], "K12": [2000,2020], "3": [2003,2026],
    "6": [2002,2026], "Demio": [1996,2019], "Axela": [2003,2019],
    "CX-3": [2015,2026], "3 Series": [1990,2026], "5 Series": [1990,2026],
    "7 Series": [1990,2026], "M3": [1992,2026], "M5": [1990,2026],
    "A-Class": [1997,2026], "C-Class": [1993,2026], "E-Class": [1990,2026],
    "S-Class": [1990,2026], "CLA": [2013,2026], "A3": [1996,2026],
    "A4": [1994,2026], "A6": [1994,2026], "A8": [1994,2026],
    "RS3": [2011,2026], "Polo": [1990,2026], "Golf": [1990,2026],
    "Passat": [1990,2026], "Jetta": [1990,2026], "Beetle": [1998,2019],
    "Lancer": [1990,2017], "Mirage": [1991,2026], "Lancer EX": [2007,2017],
    "Galant": [1990,2012], "XE": [2015,2026], "XF": [2007,2026],
    "XJ": [1997,2019], "F-Type": [2013,2026],

    "Hiace": [1990,2026], "KDH": [2004,2020],
    "Hiace Grand Cabin": [2005,2026], "Estima": [1990,2019],
    "Voxy": [2001,2026], "Noah": [2001,2026], "Caravan": [1990,2026],
    "Vanette": [1990,2026], "Elgrand": [1997,2026], "Serena": [1991,2026],
    "Every": [1990,2026], "APV": [2004,2020], "Grandis": [2003,2011],
    "Delica": [1990,2026], "Outlander": [2005,2026], "Sprinter": [1995,2026],
    "Vito": [1996,2026], "V-Class": [1996,2026],
    "Transporter": [1990,2026], "Caddy": [1995,2026],
    "Multivan": [2003,2026],

    "Fortuner": [2005,2026], "RAV4": [1994,2026],
    "Land Cruiser": [1990,2026], "Prado": [1990,2026],
    "Rush": [2006,2026], "Harrier": [1997,2026], "CR-V": [1997,2026],
    "Vezel": [2013,2026], "HR-V": [1999,2026], "BR-V": [2015,2026],
    "Pilot": [2003,2026], "X-Trail": [2000,2026], "Juke": [2010,2019],
    "Patrol": [1990,2026], "Terrano": [1993,2006],
    "Pajero": [1990,2021], "Montero": [1990,2021], "ASX": [2010,2026],
    "Sportage": [1995,2026], "Sorento": [2002,2026], "Seltos": [2019,2026],
    "Tucson": [2004,2026], "Santa Fe": [2000,2026], "Creta": [2014,2026],
    "Palisade": [2018,2026], "Defender": [1990,2026],
    "Range Rover": [1990,2026], "Discovery": [1990,2026],
    "Evoque": [2011,2026], "Velar": [2017,2026], "X1": [2009,2026],
    "X3": [2003,2026], "X5": [1999,2026], "X6": [2008,2026],
    "X7": [2018,2026], "Q3": [2011,2026], "Q5": [2008,2026],
    "Q7": [2005,2026], "Q8": [2018,2026], "F-Pace": [2016,2026],
    "E-Pace": [2017,2026], "I-Pace": [2018,2026],
    "Wrangler": [1990,2026], "Cherokee": [1990,2026],
    "Grand Cherokee": [1993,2026],

    "Dio": [1990,2026], "Hornet": [1998,2026], "CBR 250": [2011,2026],
    "Unicorn": [2004,2026], "Activa": [2000,2026], "CB350": [2020,2026],
    "FZ": [2008,2026], "R15": [2008,2026], "MT-15": [2019,2026],
    "Fazer": [2001,2020], "YZF-R1": [1998,2026], "Pulsar": [2001,2026],
    "Dominar": [2016,2026], "Discover": [2004,2020], "Avenger": [2001,2026],
    "Classic 350": [2009,2026], "Himalayan": [2016,2026],
    "Meteor 350": [2020,2026], "Interceptor 650": [2018,2026],
    "Gixxer": [2014,2026], "Access": [2007,2026],
    "V-Strom": [2004,2026], "GSX-R": [1990,2026],
    "Apache": [2005,2026], "Ntorq": [2018,2026], "Jupiter": [2013,2026],
    "Duke 200": [2012,2026], "Duke 390": [2013,2026],
    "Adventure 390": [2019,2026], "Ninja 400": [2018,2026],
    "Z900": [2017,2026], "Versys 650": [2007,2026],

    "N-Series": [1990,2026], "FRR": [1990,2026], "FTR": [1990,2026],
    "Elf": [1990,2026], "LPT": [1990,2026], "Ace": [2005,2026],
    "LPK": [1990,2026], "Xenon": [2007,2020], "Canter": [1990,2026],
    "Fighter": [1990,2026], "FK": [1990,2026], "Quon": [2004,2026],
    "Kuzer": [2016,2026], "MK": [1990,2026], "Comet": [2000,2026],
    "Husky": [2000,2026], "Captain": [2010,2026], "Atego": [1998,2026],
    "Actros": [1996,2026], "Arocs": [2013,2026],

    "Coaster": [1990,2026], "Hiace Commuter": [1990,2026],
    "Rosa": [1990,2026], "Aero": [1990,2026], "Fuso": [1990,2026],
    "County": [1998,2026], "Universe": [2006,2026], "Solati": [2015,2026],
    "Viking": [1990,2026], "Leopard": [1990,2026], "Sunbeam": [1990,2026],
    "NQR Bus": [1990,2026], "FRR Bus": [1990,2026]
};

const vehicleServices = {
    "Corolla": [
        {name:"Oil Change",description:"Engine oil and filter replacement.",price:"Rs. 5,000",time:"1 Hour"},
        {name:"Brake Service",description:"Complete brake inspection and service.",price:"Rs. 8,500",time:"2 Hours"},
        {name:"AC Service",description:"Air conditioning inspection and maintenance.",price:"Rs. 7,500",time:"2 Hours"},
        {name:"Full Vehicle Service",description:"Complete vehicle inspection and maintenance.",price:"Rs. 25,000",time:"3–4 Hours"}
    ],

    "Prius": [
        {name:"Hybrid System Service",description:"Hybrid battery and hybrid system inspection.",price:"Rs. 18,000",time:"2 Hours"},
        {name:"Oil Change",description:"Engine oil and filter replacement.",price:"Rs. 5,500",time:"1 Hour"},
        {name:"Brake Service",description:"Brake inspection and maintenance.",price:"Rs. 9,000",time:"2 Hours"}
    ],

    "Wingroad": [
        {name:"Engine Diagnostic",description:"Complete engine diagnostic inspection.",price:"Rs. 6,500",time:"1.5 Hours"},
        {name:"Oil Change",description:"Engine oil and filter replacement.",price:"Rs. 5,500",time:"1 Hour"},
        {name:"Suspension Check",description:"Suspension system inspection.",price:"Rs. 9,500",time:"2.5 Hours"}
    ],

    "Civic": [
        {name:"Oil Change",description:"Engine oil replacement.",price:"Rs. 5,500",time:"1 Hour"},
        {name:"Brake Service",description:"Brake inspection and maintenance.",price:"Rs. 9,000",time:"2 Hours"},
        {name:"Engine Service",description:"Engine inspection and maintenance.",price:"Rs. 15,000",time:"3 Hours"}
    ],

    "Defender": [
        {name:"Full 4x4 Service",description:"Complete off-road vehicle maintenance.",price:"Rs. 45,000",time:"5 Hours"},
        {name:"Suspension & Chassis Check",description:"Heavy-duty suspension inspection.",price:"Rs. 15,000",time:"3 Hours"},
        {name:"Tire Rotation & Alignment",description:"4x4 wheel alignment and tire rotation.",price:"Rs. 8,500",time:"1.5 Hours"}
    ],

    "Range Rover": [
        {name:"Full Service",description:"Complete luxury SUV maintenance.",price:"Rs. 50,000",time:"5 Hours"},
        {name:"Air Suspension Diagnostic",description:"Air suspension system inspection.",price:"Rs. 18,500",time:"3 Hours"},
        {name:"AC Service",description:"Climate control inspection and maintenance.",price:"Rs. 12,000",time:"2 Hours"}
    ]
};

const defaultServices = [
    {name:"Tire Replacement (Set of 4)",description:"Removal of old tires and installation of new tires, including balancing.",price:"Rs. 35,000",time:"2 Hours"},
    {name:"Wheel Alignment & Balancing",description:"Computerized wheel alignment and balancing.",price:"Rs. 5,500",time:"1.5 Hours"},
    {name:"Oil & Filter Change",description:"Standard engine oil and filter replacement.",price:"Rs. 7,500",time:"1 Hour"},
    {name:"Brake Inspection & Service",description:"Brake inspection, cleaning and maintenance.",price:"Rs. 10,000",time:"2 Hours"},
    {name:"Full Diagnostic & Inspection",description:"Complete vehicle health check and diagnostic scan.",price:"Rs. 8,000",time:"2 Hours"},
    {name:"Battery & Electrical Check",description:"Battery, alternator and electrical system inspection.",price:"Rs. 4,500",time:"1 Hour"},
    {name:"Vehicle Paint & Body",description:"Vehicle body repair and paint service.",price:"Rs. 35,000",time:"1–2 Days"},
    {name:"Full Body Repaint",description:"Complete exterior vehicle repainting service.",price:"Rs. 85,000",time:"3–5 Days"}
];

vehicleType.addEventListener("change", function() {
    const type = vehicleType.value;

    vehicleMake.innerHTML = '<option value="">-- Select Make --</option>';
    vehicleModel.innerHTML = '<option value="">-- Select Model --</option>';
    vehicleYear.innerHTML = '<option value="">-- Select Year --</option>';
    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");

    vehicleMake.disabled = true;
    vehicleModel.disabled = true;
    vehicleYear.disabled = true;
    findBtn.disabled = true;

    if (!type) return;
    if (!vehicleDatabase[type]) return;

    const makes = Object.keys(vehicleDatabase[type]);

    makes.forEach(function(make) {
        vehicleMake.innerHTML += `<option value="${make}">${make}</option>`;
    });

    vehicleMake.disabled = false;
});

vehicleMake.addEventListener("change", function() {
    const type = vehicleType.value;
    const make = vehicleMake.value;

    vehicleModel.innerHTML = '<option value="">-- Select Model --</option>';
    vehicleYear.innerHTML = '<option value="">-- Select Year --</option>';
    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");

    vehicleModel.disabled = true;
    vehicleYear.disabled = true;
    findBtn.disabled = true;

    if (!type || !make) return;

    if (!vehicleDatabase[type] || !vehicleDatabase[type][make]) return;

    const models = vehicleDatabase[type][make];

    models.forEach(function(model) {
        vehicleModel.innerHTML += `<option value="${model}">${model}</option>`;
    });

    vehicleModel.disabled = false;
});

vehicleModel.addEventListener("change", function() {
    const model = vehicleModel.value;

    vehicleYear.innerHTML = '<option value="">-- Select Year --</option>';
    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");
    findBtn.disabled = true;
    vehicleYear.disabled = true;

    if (!model) return;

    const range = yearRanges[model];

    if (!range) {
        const currentYear = new Date().getFullYear();

        for (let year = currentYear; year >= 1990; year--) {
            vehicleYear.innerHTML += `<option value="${year}">${year}</option>`;
        }
    } else {
        const startYear = range[0];
        const endYear = range[1];

        for (let year = endYear; year >= startYear; year--) {
            vehicleYear.innerHTML += `<option value="${year}">${year}</option>`;
        }
    }

    vehicleYear.disabled = false;
});

vehicleYear.addEventListener("change", function() {
    if (vehicleYear.value) {
        findBtn.disabled = false;
    } else {
        findBtn.disabled = true;
    }
});

window.filterServices = function() {
    const type = vehicleType.value;
    const make = vehicleMake.value;
    const model = vehicleModel.value;
    const year = vehicleYear.value;

    if (!type || !make || !model || !year) {
        alert("Please select Vehicle Type, Make, Model and Year.");
        return;
    }

    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");

    const selectedServices = vehicleServices[model] || defaultServices;
    const displayName = `${year} ${make} ${model}`;

    serviceResults.innerHTML = `
        <h2 class="result-title">
            Available Services for ${displayName}
        </h2>
    `;

    selectedServices.forEach(function(service, index) {
        serviceResults.innerHTML += `
            <div class="service-card" id="card-${index}" onclick="selectService(${index})">
                <div class="service-info">
                    <h3>${service.name}</h3>
                    <p>${service.description}</p>
                    <p>
                        <strong>Estimated Time:</strong>
                        ${service.time}
                    </p>
                </div>

                <div class="price-section">
                    <p class="price-label">Estimated Price</p>
                    <p class="price">${service.price}</p>
                    <button
                        class="book-button"
                        type="button"
                        onclick="event.stopPropagation(); selectService(${index});"
                    >
                        Select Service
                    </button>
                </div>
            </div>
        `;

        const card = document.getElementById(`card-${index}`);

        if (card) {
            card.dataset.name = service.name;
            card.dataset.price = service.price;
            card.dataset.time = service.time;
        }
    });

    serviceResults.style.display = "block";

    serviceResults.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });
};

window.selectService = function(index) {
    const selectedCard = document.getElementById(`card-${index}`);

    if (!selectedCard) {
        console.error("Service card not found: card-" + index);
        return;
    }

    document.querySelectorAll(".service-card").forEach(function(card) {
        card.classList.remove("selected");
    });

    selectedCard.classList.add("selected");

    document.querySelectorAll(".book-button").forEach(function(button) {
        button.innerText = "Select Service";
    });

    const selectedButton = selectedCard.querySelector(".book-button");

    if (selectedButton) {
        selectedButton.innerText = "Selected ✓";
    }

    const year = vehicleYear.value;
    const make = vehicleMake.value;
    const model = vehicleModel.value;
    const fullVehicleName = `${year} ${make} ${model}`;

    const summaryVehicle = document.getElementById("summaryVehicle");
    const summaryService = document.getElementById("summaryService");
    const summaryTime = document.getElementById("summaryTime");
    const summaryPrice = document.getElementById("summaryPrice");

    if (summaryVehicle) {
        summaryVehicle.innerText = fullVehicleName;
    }

    if (summaryService) {
        summaryService.innerText = selectedCard.dataset.name;
    }

    if (summaryTime) {
        summaryTime.innerText = selectedCard.dataset.time;
    }

    if (summaryPrice) {
        summaryPrice.innerText = selectedCard.dataset.price;
    }

    checkoutSection.classList.remove("hidden");

    checkoutSection.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });
};

window.proceedToPayment = function() {
    const vehicleElement = document.getElementById("summaryVehicle");
    const serviceElement = document.getElementById("summaryService");
    const timeElement = document.getElementById("summaryTime");
    const priceElement = document.getElementById("summaryPrice");

    if (!vehicleElement || !serviceElement || !timeElement || !priceElement) {
        alert("Order information could not be found.");
        return;
    }

    const vehicle = vehicleElement.innerText;
    const service = serviceElement.innerText;
    const time = timeElement.innerText;
    const price = priceElement.innerText;

    if (!vehicle || vehicle === "N/A" || !service || service === "N/A") {
        alert("Please select a service first.");
        return;
    }

    fetch("../invoice/save_booking.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            vehicle: vehicle,
            service: service,
            time: time,
            price: price
        })
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            window.location.href = `../invoice/invoice.php?id=${data.booking_id}`;
        } else {
            alert("Error saving booking: " + data.message);
        }
    })
    .catch(function(error) {
        console.error("Booking error:", error);
        alert("Network error while saving booking.");
    });
};
