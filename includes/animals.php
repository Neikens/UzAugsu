<?php

$biblical_animals = [

    "Lauva", "Aita", "Jērs", "Āzis", "Kamielis", "Zirgs", 
    "Ēzelis", "Vērsis", "Govs", "Briedis", "Stirna", "Kazlēns",
    "Lapsa", "Vilks", "Lācis", "Leopards", "Bebrs", "Zaķis",
    "Suns", "Kaķis", "Mežacūka", "Savvaļas āzis", "Bufals",
    

    "Ērglis", "Balodis", "Krauklis", "Vanags", "Pūce",
    "Gailis", "Vista", "Gulbis", "Strazds", "Lakstīgala",
    "Žagata", "Pūpēdis", "Dzērve", "Zvirbulis", "Bezdelīga",
    "Strauss", "Fazāns", "Pāva", "Pīle", "Zoss",
    

    "Zivs", "Valis", "Delfīns", "Garnele", "Krabis",
    

    "Čūska", "Skorpions", "Siseņa", "Bite", "Skudra",
    "Vabole", "Tauriņš", "Spāre", "Sikspārnis", "Vardе",
    "Bruņurupucis", "Ķirzaka", "Sienāzis",
    

    "Stiprais Lauva", "Drosmīgā Aita", "Uzticīgais Suns",
    "Gudrs Pelēns", "Ātais Briedis", "Pacietīgais Kamielis",
    "Maigais Jērs", "Brīvais Ērglis", "Modrs Vanaks",
    "Klusais Balodis", "Strādīgā Bite", "Gudra Skudra",
    "Spēcīgais Zirgs", "Mierīgā Stirna", "Drosmīgais Ērglis",
    

    "Gaismas Balodis", "Miera Jērs", "Spēka Lauva",
    "Cerības Ērglis", "Ticības Suns", "Gudrības Pūce",
    "Prieka Lakstīgala", "Uzvaras Vanaks", "Mīlestības Zvirbulis"
];

function getRandomAnimalName() {
    global $biblical_animals;
    return $biblical_animals[array_rand($biblical_animals)];
}
