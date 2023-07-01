
const fs = require('fs');
var json = require('../test.json');

var bolumler = [];

for (let index = 0; index < json.length; index++) {
    const element = json[index];
    bolumler.push(element['bolum_adi']);   
}

function onlyUnique(value, index, array) {
    return array.indexOf(value) === index;
}

  
var unique_values = bolumler.filter(onlyUnique);

fs.writeFile("distint.json", JSON.stringify(unique_values), function(err) {
    if(err) {
        return console.log(err);
    }
    console.log("The file was saved!");
}); 