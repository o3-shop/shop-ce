<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

//shop location countries - used when loading dynamic content from oxid servers
$aLanguages = [

    'en' => 'English',
    'de' => 'Deutsch',

];

$aLocationCountries['en'] = [

    'de' => 'Germany, Austria, Switzerland',
    'en' => 'Any other',

];

$aLocationCountries['de'] = [

    'de' => 'Deutschland, Österreich, Schweiz',
    'en' => 'Andere Region',

];

$aCountries['en'] = [

    "a7c40f6320aeb2ec2.72885259" => "Austria",
    "a7c40f63272a57296.32117580" => "France",
    "a7c40f631fc920687.20179984" => "Germany",
    "a7c40f632a0804ab5.18804076" => "United Kingdom",
    "8f241f11096877ac0.98748826" => "United States",
    "a7c40f6321c6f6109.43859248" => "Switzerland",
    "8f241f11095306451.36998225" => "Afghanistan",
    "8f241f110953265a5.25286134" => "Albania",
    "8f241f1109533b943.50287900" => "Algeria",
    "8f241f1109534f8c7.80349931" => "American Samoa",
    "8f241f11095363464.89657222" => "Andorra",
    "8f241f11095377d33.28678901" => "Angola",
    "8f241f11095392e41.74397491" => "Anguilla",
    "8f241f110953a8d10.29474848" => "Antarctica",
    "8f241f110953be8f2.56248134" => "Antigua and Barbuda",
    "8f241f110953d2fb0.54260547" => "Argentina",
    "8f241f110953e7993.88180360" => "Armenia",
    "8f241f110953facc6.31621036" => "Aruba",
    "8f241f11095410f38.37165361" => "Australia",
    "8f241f1109543cf47.17877015" => "Azerbaijan",
    "8f241f11095451379.72078871" => "Bahamas",
    "8f241f110954662e3.27051654" => "Bahrain",
    "8f241f1109547ae49.60154431" => "Bangladesh",
    "8f241f11095497083.21181725" => "Barbados",
    "8f241f110954ac5b9.63105203" => "Belarus",
    "a7c40f632e04633c9.47194042" => "Belgium",
    "8f241f110954d3621.45362515" => "Belize",
    "8f241f110954ea065.41455848" => "Benin",
    "8f241f110954fee13.50011948" => "Bermuda",
    "8f241f11095513ca0.75349731" => "Bhutan",
    "8f241f1109552aee2.91004965" => "Bolivia",
    "8f241f1109553f902.06960438" => "Bosnia and Herzegovina",
    "8f241f11095554834.54199483" => "Botswana",
    "8f241f1109556dd57.84292282" => "Bouvet Island",
    "8f241f11095592407.89986143" => "Brazil",
    "8f241f110955a7644.68859180" => "British Indian Ocean Territory",
    "8f241f110955bde61.63256042" => "Brunei Darussalam",
    "8f241f110955d3260.55487539" => "Bulgaria",
    "8f241f110955ea7c8.36762654" => "Burkina Faso",
    "8f241f110956004d5.11534182" => "Burundi",
    "8f241f110956175f9.81682035" => "Cambodia",
    "8f241f11095632828.20263574" => "Cameroon",
    "8f241f11095649d18.02676059" => "Canada",
    "8f241f1109565e671.48876354" => "Cape Verde",
    "8f241f11095673248.50405852" => "Cayman Islands",
    "8f241f1109568a509.03566030" => "Central African Republic",
    "8f241f1109569d4c2.42800039" => "Chad",
    "8f241f110956b3ea7.11168270" => "Chile",
    "8f241f110956c8860.37981845" => "China",
    "8f241f110956df6b2.52283428" => "Christmas Island",
    "8f241f110956f54b4.26327849" => "Cocos (Keeling) Islands",
    "8f241f1109570a1e3.69772638" => "Colombia",
    "8f241f1109571f018.46251535" => "Comoros",
    "8f241f11095732184.72771986" => "Congo",
    "8f241f1109575d708.20084199" => "Congo, The Democratic Republic Of The",
    "8f241f11095746a92.94878441" => "Cook Islands",
    "8f241f1109575d708.20084150" => "Costa Rica",
    "8f241f11095771f76.87904122" => "Cote d'Ivoire",
    "8f241f11095789a04.65154246" => "Croatia",
    "8f241f1109579ef49.91803242" => "Cuba",
    "8f241f110957b6896.52725150" => "Cyprus",
    "8f241f110957cb457.97820918" => "Czech Republic",
    "8f241f110957e6ef8.56458418" => "Denmark",
    "8f241f110957fd356.02918645" => "Djibouti",
    "8f241f11095811ea5.84717844" => "Dominica",
    "8f241f11095825bf2.61063355" => "Dominican Republic",
    "8f241f1109584d512.06663789" => "Ecuador",
    "8f241f11095861fb7.55278256" => "Egypt",
    "8f241f110958736a9.06061237" => "El Salvador",
    "8f241f1109588d077.74284490" => "Equatorial Guinea",
    "8f241f110958a2216.38324531" => "Eritrea",
    "8f241f110958b69e4.93886171" => "Estonia",
    "8f241f110958caf67.08982313" => "Ethiopia",
    "8f241f110958e2cc3.90770249" => "Falkland Islands (Malvinas)",
    "8f241f110958f7ba4.96908065" => "Faroe Islands",
    "8f241f1109590d226.07938729" => "Fiji",
    "a7c40f63293c19d65.37472814" => "Finland",
    "8f241f1109594fcb1.79441780" => "French Guiana",
    "8f241f110959636f5.71476354" => "French Polynesia",
    "8f241f110959784a3.34264829" => "French Southern Territories",
    "8f241f11095994cb6.59353392" => "Gabon",
    "8f241f110959ace77.17379319" => "Gambia",
    "8f241f110959c2341.01830199" => "Georgia",
    "8f241f110959e96b3.05752152" => "Ghana",
    "8f241f110959fdde0.68919405" => "Gibraltar",
    "a7c40f633114e8fc6.25257477" => "Greece",
    "8f241f11095a29f47.04102343" => "Greenland",
    "8f241f11095a3f195.88886789" => "Grenada",
    "8f241f11095a52578.45413493" => "Guadeloupe",
    "8f241f11095a717b3.68126681" => "Guam",
    "8f241f11095a870a5.42235635" => "Guatemala",
    "56d308a822c18e106.3ba59099" => "Guernsey",
    "8f241f11095a9bf82.19989557" => "Guinea",
    "8f241f11095ab2b56.83049280" => "Guinea-Bissau",
    "8f241f11095ac9d30.56640429" => "Guyana",
    "8f241f11095aebb06.34405179" => "Haiti",
    "8f241f11095aff2c3.98054755" => "Heard Island And Mcdonald Islands",
    "8f241f110968ebc30.63792746" => "Holy See (Vatican City State)",
    "8f241f11095b13f57.56022305" => "Honduras",
    "8f241f11095b29021.49657118" => "Hong Kong",
    "8f241f11095b3e016.98213173" => "Hungary",
    "8f241f11095b55846.26192602" => "Iceland",
    "8f241f11095b6bb86.01364904" => "India",
    "8f241f11095b80526.59927631" => "Indonesia",
    "8f241f11095b94476.05195832" => "Iran",
    "8f241f11095bad5b2.42645724" => "Iraq",
    "a7c40f632be4237c2.48517912" => "Ireland",
    "8f241f11096982354.73448999" => "Isle Of Man",
    "8f241f11095bd65e1.59459683" => "Israel",
    "a7c40f6323c4bfb36.59919433" => "Italy",
    "8f241f11095bfe834.63390185" => "Jamaica",
    "8f241f11095c11d43.73419747" => "Japan",
    "8f241f11096944468.61956599" => "Jersey",
    "8f241f11095c2b304.75906962" => "Jordan",
    "8f241f11095c3e2d1.36714463" => "Kazakhstan",
    "8f241f11095c5b8e8.66333679" => "Kenya",
    "8f241f11095c6e184.21450618" => "Kiribati",
    "8f241f11095cb1546.46652174" => "Kuwait",
    "8f241f11095cc7ef5.28043767" => "Kyrgyzstan",
    "8f241f11095cdccd5.96388808" => "Laos",
    "8f241f11095cf2ea6.73925511" => "Latvia",
    "8f241f11095d07d87.58986129" => "Lebanon",
    "8f241f11095d1c9b2.21548132" => "Lesotho",
    "8f241f11095d2fd28.91858908" => "Liberia",
    "8f241f11095d46188.64679605" => "Libya",
    "a7c40f6322d842ae3.83331920" => "Liechtenstein",
    "8f241f11095d6ffa8.86593236" => "Lithuania",
    "a7c40f63264309e05.58576680" => "Luxembourg",
    "8f241f11095d9c1b2.13577033" => "Macao",
    "8f241f11095db2291.58912887" => "Macedonia",
    "8f241f11095dccf17.06266806" => "Madagascar",
    "8f241f11095de2119.60795833" => "Malawi",
    "8f241f11095df78a8.44559506" => "Malaysia",
    "8f241f11095e0c6c9.43746477" => "Maldives",
    "8f241f11095e24006.17141715" => "Mali",
    "8f241f11095e36eb3.69050509" => "Malta",
    "8f241f11095e4e338.26817244" => "Marshall Islands",
    "8f241f11095e631e1.29476484" => "Martinique",
    "8f241f11095e7bff9.09518271" => "Mauritania",
    "8f241f11095e90a81.01156393" => "Mauritius",
    "8f241f11095ea6249.81474246" => "Mayotte",
    "8f241f11095ebf3a6.86388577" => "Mexico",
    "8f241f11095ed4902.49276197" => "Micronesia, Federated States Of",
    "8f241f11095ee9923.85175653" => "Moldova",
    "8f241f11095f00d65.30318330" => "Monaco",
    "8f241f11095f160c9.41059441" => "Mongolia",
    "56d308a822c18e106.3ba59048" => "Montenegro",
    "8f241f11095f314f5.05830324" => "Montserrat",
    "8f241f11096006828.49285591" => "Morocco",
    "8f241f1109601b419.55269691" => "Mozambique",
    "8f241f11096030af5.65449043" => "Myanmar",
    "8f241f11096046575.31382060" => "Namibia",
    "8f241f1109605b1f4.20574895" => "Nauru",
    "8f241f1109607a9e7.03486450" => "Nepal",
    "a7c40f632cdd63c52.64272623" => "Netherlands",
    "8f241f110960aeb64.09757010" => "Netherlands Antilles",
    "8f241f110960c3e97.21901471" => "New Caledonia",
    "8f241f110960d8e58.96466103" => "New Zealand",
    "8f241f110960ec345.71805056" => "Nicaragua",
    "8f241f11096101a79.70513227" => "Niger",
    "8f241f11096116744.92008092" => "Nigeria",
    "8f241f1109612dc68.63806992" => "Niue",
    "8f241f110961442c2.82573898" => "Norfolk Island",
    "8f241f11095c87284.37982544" => "North Korea",
    "8f241f11096162678.71164081" => "Northern Mariana Islands",
    "8f241f11096176795.61257067" => "Norway",
    "8f241f1109618d825.87661926" => "Oman",
    "2db455824e4a19cc7.14731328" => "Other country",
    "8f241f110961a2401.59039740" => "Pakistan",
    "8f241f110961b7729.14290490" => "Palau",
    "8f241f110968ebc30.63792799" => "Palestinian Territory, Occupied",
    "8f241f110961cc384.18166560" => "Panama",
    "8f241f110961e3538.78435307" => "Papua New Guinea",
    "8f241f110961f9d61.52794273" => "Paraguay",
    "8f241f1109620b245.16261506" => "Peru",
    "8f241f1109621faf8.40135556" => "Philippines",
    "8f241f11096234d62.44125992" => "Pitcairn",
    "8f241f1109624d3f8.50953605" => "Poland",
    "a7c40f632f65bd8e2.84963272" => "Portugal",
    "8f241f11096279a22.50582479" => "Puerto Rico",
    "8f241f1109628f903.51478291" => "Qatar",
    "8f241f110962a3ec5.65857240" => "Réunion",
    "8f241f110962c3007.60363573" => "Romania",
    "8f241f110962e40e6.75062153" => "Russian Federation",
    "8f241f110962f8615.93666560" => "Rwanda",
    "8f241f110968a7cc9.56710199" => "Saint Barthélemy",
    "8f241f1109654dca4.99466434" => "Saint Helena",
    "8f241f110963177a7.49289900" => "Saint Kitts and Nevis",
    "8f241f1109632fab4.68646740" => "Saint Lucia",
    "a7c40f632f65bd8e2.84963299" => "Saint Martin",
    "8f241f1109656cde9.10816078" => "Saint Pierre and Miquelon",
    "8f241f110963443c3.29598809" => "Saint Vincent and The Grenadines",
    "8f241f11096359986.06476221" => "Samoa",
    "8f241f11096375757.44126946" => "San Marino",
    "8f241f1109639b8c4.57484984" => "Sao Tome and Principe",
    "8f241f110963b9b20.41500709" => "Saudi Arabia",
    "8f241f110963d9962.36307144" => "Senegal",
    "8f241f110963f98d8.68428379" => "Serbia",
    "8f241f11096418496.77253079" => "Seychelles",
    "8f241f11096436968.69551351" => "Sierra Leone",
    "8f241f11096456a48.79608805" => "Singapore",
    "8f241f1109647a265.29938154" => "Slovakia",
    "8f241f11096497149.85116254" => "Slovenia",
    "8f241f110964b7bf9.49501835" => "Solomon Islands",
    "8f241f110964d5f29.11398308" => "Somalia",
    "8f241f110964f2623.74976876" => "South Africa",
    "8f241f1109533b943.50287999" => "South Georgia and The South Sandwich Islands",
    "8f241f11095c9de64.01275726" => "South Korea",
    "a7c40f633038cd578.22975442" => "Spain",
    "8f241f11096531330.03198083" => "Sri Lanka",
    "8f241f1109658cbe5.08293991" => "Sudan",
    "8f241f110965c7347.75108681" => "Suriname",
    "8f241f110965eb7b7.26149742" => "Svalbard and Jan Mayen",
    "8f241f1109660c113.62780718" => "Swaziland",
    "a7c40f632848c5217.53322339" => "Sweden",
    "8f241f1109666b7f3.81435898" => "Syria",
    "8f241f11096687ec7.58824735" => "Taiwan, Province of China",
    "8f241f110966a54d1.43798997" => "Tajikistan",
    "8f241f110966c3a75.68297960" => "Tanzania",
    "8f241f11096707e08.60512709" => "Thailand",
    "8f241f11095839323.86755169" => "Timor-Leste",
    "8f241f110967241e1.34925220" => "Togo",
    "8f241f11096742565.72138875" => "Tokelau",
    "8f241f11096762b31.03069244" => "Tonga",
    "8f241f1109677ed23.84886671" => "Trinidad and Tobago",
    "8f241f1109679d988.46004322" => "Tunisia",
    "8f241f110967bba40.88233204" => "Turkey",
    "8f241f110967d8f65.52699796" => "Turkmenistan",
    "8f241f110967f73f8.13141492" => "Turks and Caicos Islands",
    "8f241f1109680ec30.97426963" => "Tuvalu",
    "8f241f11096823019.47846368" => "Uganda",
    "8f241f110968391d2.37199812" => "Ukraine",
    "8f241f1109684bf15.63071279" => "United Arab Emirates",
    "8f241f11096894977.41239553" => "United States Minor Outlying Islands",
    "8f241f110968a7cc9.56710143" => "Uruguay",
    "8f241f110968bec45.44161857" => "Uzbekistan",
    "8f241f110968d3f03.13630334" => "Vanuatu",
    "8f241f11096902d92.14742486" => "Venezuela",
    "8f241f11096919d00.92534927" => "Vietnam",
    "8f241f1109692fc04.15216034" => "Virgin Islands, British",
    "8f241f11096944468.61956573" => "Virgin Islands, U.S.",
    "8f241f110969598c8.76966113" => "Wallis and Futuna",
    "8f241f1109696e4e9.33006292" => "Western Sahara",
    "8f241f11096982354.73448958" => "Yemen",
    "8f241f110969c34a2.42564730" => "Zambia",
    "8f241f110969da699.04185888" => "Zimbabwe",
    "a7c40f632a0804ab5.18804099" => "Åland Islands",
];

$aCountries['de'] = [

    "a7c40f6320aeb2ec2.72885259" => "Österreich",
    "a7c40f63272a57296.32117580" => "Frankreich",
    "a7c40f631fc920687.20179984" => "Deutschland",
    "a7c40f6321c6f6109.43859248" => "Schweiz",
    "a7c40f632a0804ab5.18804076" => "Vereinigtes Königreich",
    "8f241f11096877ac0.98748826" => "Vereinigte Staaten von Amerika",
    "8f241f11095306451.36998225" => "Afghanistan",
    "8f241f110953265a5.25286134" => "Albanien",
    "8f241f1109533b943.50287900" => "Algerien",
    "8f241f1109534f8c7.80349931" => "Amerikanisch Samoa",
    "8f241f11096944468.61956573" => "Amerikanische Jungferninseln",
    "2db455824e4a19cc7.14731328" => "Anderes Land",
    "8f241f11095363464.89657222" => "Andorra",
    "8f241f11095377d33.28678901" => "Angola",
    "8f241f11095392e41.74397491" => "Anguilla",
    "8f241f110953a8d10.29474848" => "Antarktis",
    "8f241f110953be8f2.56248134" => "Antigua und Barbuda",
    "8f241f110953d2fb0.54260547" => "Argentinien",
    "8f241f110953e7993.88180360" => "Armenien",
    "8f241f110953facc6.31621036" => "Aruba",
    "8f241f1109543cf47.17877015" => "Aserbaidschan",
    "8f241f11095410f38.37165361" => "Australien",
    "8f241f11095451379.72078871" => "Bahamas",
    "8f241f110954662e3.27051654" => "Bahrain",
    "8f241f1109547ae49.60154431" => "Bangladesch",
    "8f241f11095497083.21181725" => "Barbados",
    "a7c40f632e04633c9.47194042" => "Belgien",
    "8f241f110954d3621.45362515" => "Belize",
    "8f241f110954ea065.41455848" => "Benin",
    "8f241f110954fee13.50011948" => "Bermuda",
    "8f241f11095513ca0.75349731" => "Bhutan",
    "8f241f1109552aee2.91004965" => "Bolivien",
    "8f241f1109553f902.06960438" => "Bosnien und Herzegowina",
    "8f241f11095554834.54199483" => "Botsuana",
    "8f241f1109556dd57.84292282" => "Bouvetinsel",
    "8f241f11095592407.89986143" => "Brasilien",
    "8f241f1109692fc04.15216034" => "Britische Jungferninseln",
    "8f241f110955a7644.68859180" => "Britisches Territorium im Indischen Ozean",
    "8f241f110955bde61.63256042" => "Brunei Darussalam",
    "8f241f110955d3260.55487539" => "Bulgarien",
    "8f241f110955ea7c8.36762654" => "Burkina Faso",
    "8f241f110956004d5.11534182" => "Burundi",
    "8f241f110956b3ea7.11168270" => "Chile",
    "8f241f110956c8860.37981845" => "China",
    "8f241f11095746a92.94878441" => "Cookinseln",
    "8f241f1109575d708.20084150" => "Costa Rica",
    "8f241f11095771f76.87904122" => "Elfenbeinküste",
    "8f241f11095811ea5.84717844" => "Dominica",
    "8f241f11095825bf2.61063355" => "Dominikanische Republik",
    "8f241f110957fd356.02918645" => "Dschibuti",
    "8f241f110957e6ef8.56458418" => "Dänemark",
    "8f241f1109584d512.06663789" => "Ecuador",
    "8f241f110958736a9.06061237" => "El Salvador",
    "8f241f110958a2216.38324531" => "Eritrea",
    "8f241f110958b69e4.93886171" => "Estland",
    "8f241f110958e2cc3.90770249" => "Falklandinseln (Malwinen)",
    "8f241f1109590d226.07938729" => "Fidschi",
    "a7c40f63293c19d65.37472814" => "Finnland",
    "8f241f1109594fcb1.79441780" => "Französisch Guiana",
    "8f241f110959636f5.71476354" => "Französisch-Polynesien",
    "8f241f110959784a3.34264829" => "Französische Südgebiete",
    "8f241f110958f7ba4.96908065" => "Färöer",
    "8f241f11095994cb6.59353392" => "Gabun",
    "8f241f110959ace77.17379319" => "Gambia",
    "8f241f110959c2341.01830199" => "Georgien",
    "8f241f110959e96b3.05752152" => "Ghana",
    "8f241f110959fdde0.68919405" => "Gibraltar",
    "8f241f11095a3f195.88886789" => "Grenada",
    "a7c40f633114e8fc6.25257477" => "Griechenland",
    "8f241f11095a29f47.04102343" => "Grönland",
    "8f241f11095a52578.45413493" => "Guadeloupe",
    "8f241f11095a717b3.68126681" => "Guam",
    "8f241f11095a870a5.42235635" => "Guatemala",
    "56d308a822c18e106.3ba59099" => "Guernsey",
    "8f241f11095a9bf82.19989557" => "Guinea",
    "8f241f11095ab2b56.83049280" => "Guinea-Bissau",
    "8f241f11095ac9d30.56640429" => "Guyana",
    "8f241f11095aebb06.34405179" => "Haiti",
    "8f241f11095aff2c3.98054755" => "Heard Insel und McDonald Inseln",
    "8f241f110968ebc30.63792746" => "Heiliger Stuhl (Vatikanstadt)",
    "8f241f11095b13f57.56022305" => "Honduras",
    "8f241f11095b29021.49657118" => "Hong Kong",
    "8f241f11095b6bb86.01364904" => "Indien",
    "8f241f11095b80526.59927631" => "Indonesien",
    "8f241f11095bad5b2.42645724" => "Irak",
    "8f241f11095b94476.05195832" => "Iran",
    "a7c40f632be4237c2.48517912" => "Irland",
    "8f241f11095b55846.26192602" => "Island",
    "8f241f11096982354.73448999" => "Isle of Man",
    "8f241f11095bd65e1.59459683" => "Israel",
    "a7c40f6323c4bfb36.59919433" => "Italien",
    "8f241f11095bfe834.63390185" => "Jamaika",
    "8f241f11095c11d43.73419747" => "Japan",
    "8f241f11096982354.73448958" => "Jemen",
    "8f241f11096944468.61956599" => "Jersey",
    "8f241f11095c2b304.75906962" => "Jordanien",
    "8f241f11095673248.50405852" => "Kaimaninseln",
    "8f241f110956175f9.81682035" => "Kambodscha",
    "8f241f11095632828.20263574" => "Kamerun",
    "8f241f11095649d18.02676059" => "Kanada",
    "8f241f1109565e671.48876354" => "Kap Verde",
    "8f241f11095c3e2d1.36714463" => "Kasachstan",
    "8f241f1109628f903.51478291" => "Katar",
    "8f241f11095c5b8e8.66333679" => "Kenia",
    "8f241f11095cc7ef5.28043767" => "Kirgisistan",
    "8f241f11095c6e184.21450618" => "Kiribati",
    "8f241f110956f54b4.26327849" => "Kokosinseln (Keelinginseln)",
    "8f241f1109570a1e3.69772638" => "Kolumbien",
    "8f241f1109571f018.46251535" => "Komoren",
    "8f241f11095732184.72771986" => "Kongo",
    "8f241f1109575d708.20084199" => "Kongo, Dem. Rep.",
    "8f241f11095789a04.65154246" => "Kroatien",
    "8f241f1109579ef49.91803242" => "Kuba",
    "8f241f11095cb1546.46652174" => "Kuwait",
    "8f241f11095cdccd5.96388808" => "Laos",
    "8f241f11095d1c9b2.21548132" => "Lesotho",
    "8f241f11095cf2ea6.73925511" => "Lettland",
    "8f241f11095d07d87.58986129" => "Libanon",
    "8f241f11095d2fd28.91858908" => "Liberia",
    "8f241f11095d46188.64679605" => "Libyen",
    "a7c40f6322d842ae3.83331920" => "Liechtenstein",
    "8f241f11095d6ffa8.86593236" => "Litauen",
    "a7c40f63264309e05.58576680" => "Luxemburg",
    "8f241f11095d9c1b2.13577033" => "Macao",
    "8f241f11095dccf17.06266806" => "Madagaskar",
    "8f241f11095de2119.60795833" => "Malawi",
    "8f241f11095df78a8.44559506" => "Malaysia",
    "8f241f11095e0c6c9.43746477" => "Malediven",
    "8f241f11095e24006.17141715" => "Mali",
    "8f241f11095e36eb3.69050509" => "Malta",
    "8f241f11096006828.49285591" => "Marokko",
    "8f241f11095e4e338.26817244" => "Marshallinseln",
    "8f241f11095e631e1.29476484" => "Martinique",
    "8f241f11095e7bff9.09518271" => "Mauretanien",
    "8f241f11095e90a81.01156393" => "Mauritius",
    "8f241f11095ea6249.81474246" => "Mayotte",
    "8f241f11095db2291.58912887" => "Mazedonien",
    "8f241f11095ebf3a6.86388577" => "Mexiko",
    "8f241f11095ed4902.49276197" => "Mikronesien, Föderierte Staaten von",
    "8f241f11095ee9923.85175653" => "Moldawien",
    "8f241f11095f00d65.30318330" => "Monaco",
    "8f241f11095f160c9.41059441" => "Mongolei",
    "56d308a822c18e106.3ba59048" => "Montenegro",
    "8f241f11095f314f5.05830324" => "Montserrat",
    "8f241f1109601b419.55269691" => "Mosambik",
    "8f241f11096030af5.65449043" => "Myanmar",
    "8f241f11096046575.31382060" => "Namibia",
    "8f241f1109605b1f4.20574895" => "Nauru",
    "8f241f1109607a9e7.03486450" => "Nepal",
    "8f241f110960c3e97.21901471" => "Neukaledonien",
    "8f241f110960d8e58.96466103" => "Neuseeland",
    "8f241f110960ec345.71805056" => "Nicaragua",
    "a7c40f632cdd63c52.64272623" => "Niederlande",
    "8f241f110960aeb64.09757010" => "Niederländische Antillen",
    "8f241f11096101a79.70513227" => "Niger",
    "8f241f11096116744.92008092" => "Nigeria",
    "8f241f1109612dc68.63806992" => "Niue",
    "8f241f11095c87284.37982544" => "Nordkorea",
    "8f241f110961442c2.82573898" => "Norfolkinsel",
    "8f241f11096176795.61257067" => "Norwegen",
    "8f241f11096162678.71164081" => "Nördliche Marianen",
    "8f241f1109618d825.87661926" => "Oman",
    "8f241f110961a2401.59039740" => "Pakistan",
    "8f241f110961b7729.14290490" => "Palau",
    "8f241f110968ebc30.63792799" => "Palästinische Gebiete",
    "8f241f110961cc384.18166560" => "Panama",
    "8f241f110961e3538.78435307" => "Papua-Neuguinea",
    "8f241f110961f9d61.52794273" => "Paraguay",
    "8f241f1109620b245.16261506" => "Peru",
    "8f241f1109621faf8.40135556" => "Philippinen",
    "8f241f11096234d62.44125992" => "Pitcairn",
    "8f241f1109624d3f8.50953605" => "Polen",
    "a7c40f632f65bd8e2.84963272" => "Portugal",
    "8f241f11096279a22.50582479" => "Puerto Rico",
    "8f241f11096687ec7.58824735" => "Republik China (Taiwan)",
    "8f241f110962a3ec5.65857240" => "Réunion",
    "8f241f110962f8615.93666560" => "Ruanda",
    "8f241f110962c3007.60363573" => "Rumänien",
    "8f241f110962e40e6.75062153" => "Russische Föderation",
    "8f241f1109654dca4.99466434" => "Saint Helena",
    "8f241f1109656cde9.10816078" => "Saint Pierre und Miquelon",
    "8f241f110968a7cc9.56710199" => "Saint-Barthélemy",
    "a7c40f632f65bd8e2.84963299" => "Saint-Martin",
    "8f241f110964b7bf9.49501835" => "Salomonen",
    "8f241f110969c34a2.42564730" => "Sambia",
    "8f241f11096359986.06476221" => "Samoa",
    "8f241f11096375757.44126946" => "San Marino",
    "8f241f1109639b8c4.57484984" => "Sao Tome und Principe",
    "8f241f110963b9b20.41500709" => "Saudi-Arabien",
    "a7c40f632848c5217.53322339" => "Schweden",
    "8f241f110963d9962.36307144" => "Senegal",
    "8f241f110963f98d8.68428379" => "Serbien",
    "8f241f11096418496.77253079" => "Seychellen",
    "8f241f11096436968.69551351" => "Sierra Leone",
    "8f241f110969da699.04185888" => "Simbabwe",
    "8f241f11096456a48.79608805" => "Singapur",
    "8f241f1109647a265.29938154" => "Slowakei",
    "8f241f11096497149.85116254" => "Slowenien",
    "8f241f110964d5f29.11398308" => "Somalia",
    "a7c40f633038cd578.22975442" => "Spanien",
    "8f241f11096531330.03198083" => "Sri Lanka",
    "8f241f110963177a7.49289900" => "St. Kitts und Nevis",
    "8f241f1109632fab4.68646740" => "St. Lucia",
    "8f241f110963443c3.29598809" => "St. Vincent und die Grenadinen",
    "8f241f1109658cbe5.08293991" => "Sudan",
    "8f241f110965c7347.75108681" => "Suriname",
    "8f241f110965eb7b7.26149742" => "Svalbard und Jan Mayen",
    "8f241f1109660c113.62780718" => "Swasiland",
    "8f241f110964f2623.74976876" => "Südafrika",
    "8f241f1109533b943.50287999" => "Südgeorgien und die Südlichen Sandwichinseln",
    "8f241f11095c9de64.01275726" => "Südkorea",
    "8f241f1109666b7f3.81435898" => "Syrien",
    "8f241f110966a54d1.43798997" => "Tadschikistan",
    "8f241f110966c3a75.68297960" => "Tansania",
    "8f241f11096707e08.60512709" => "Thailand",
    "8f241f11095839323.86755169" => "Timor-Leste",
    "8f241f110967241e1.34925220" => "Togo",
    "8f241f11096742565.72138875" => "Tokelau",
    "8f241f11096762b31.03069244" => "Tonga",
    "8f241f1109677ed23.84886671" => "Trinidad und Tobago",
    "8f241f1109569d4c2.42800039" => "Tschad",
    "8f241f110957cb457.97820918" => "Tschechische Republik",
    "8f241f1109679d988.46004322" => "Tunesien",
    "8f241f110967d8f65.52699796" => "Turkmenistan",
    "8f241f110967f73f8.13141492" => "Turks- und Caicosinseln",
    "8f241f1109680ec30.97426963" => "Tuvalu",
    "8f241f110967bba40.88233204" => "Türkei",
    "8f241f11096823019.47846368" => "Uganda",
    "8f241f110968391d2.37199812" => "Ukraine",
    "8f241f11095b3e016.98213173" => "Ungarn",
    "8f241f11096894977.41239553" => "United States Minor Outlying Islands",
    "8f241f110968a7cc9.56710143" => "Uruguay",
    "8f241f110968bec45.44161857" => "Usbekistan",
    "8f241f110968d3f03.13630334" => "Vanuatu",
    "8f241f11096902d92.14742486" => "Venezuela",
    "8f241f1109684bf15.63071279" => "Vereinigte Arabische Emirate",
    "8f241f11096919d00.92534927" => "Vietnam",
    "8f241f110969598c8.76966113" => "Wallis und Futuna",
    "8f241f110956df6b2.52283428" => "Weihnachtsinsel",
    "8f241f110954ac5b9.63105203" => "Weißrussland",
    "8f241f1109696e4e9.33006292" => "Westsahara",
    "8f241f1109568a509.03566030" => "Zentralafrikanische Republik",
    "8f241f110957b6896.52725150" => "Zypern",
    "a7c40f632a0804ab5.18804099" => "Åland Inseln",
    "8f241f11095861fb7.55278256" => "Ägypten",
    "8f241f1109588d077.74284490" => "Äquatorialguinea",
    "8f241f110958caf67.08982313" => "Äthiopien",
];
