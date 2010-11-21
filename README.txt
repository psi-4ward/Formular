Klasse zur Erstellung von Formularen anhand eines DCA-Arrays.

=== Features ===
================
* Kompatibel mit den Erweiterungen: cleardefault, helpmessage, formcheck
* tincMCE
* Datepicker
* Verschiedene Generate-Arten (Anordnung Laben,Feld,Fehlermeldung)
* Optionale TL_DCA Anbindung


=== Installation ===
====================
Formular.php und FormularTL_DCA.php nach system/libraries kopieren.


=== Konfiguration ===
=====================
Alle Optionen können im $GLOBALS['TL_FORM']-Array systemweit vorgegeben werden.
Eine Formular-Instanz versucht diese Konfiguration zu übernehmen.
Weitere Optionen können über die Methode setConfig($param,$value) übergeben werden.

Parameter:
	str formTemplate: 	Wird das Formular über parse() gerendert wird dieses Template benutzt. Vorgabe: form
	str class:			Zusätzliche Klassenangabe
	str action:			action-Attribut des <form> Tags. Vorgabe: Environment->request
	str method:			method-Attribut des <form> Tags. Vorgabe: post
	str generateFormat: String mit Platzhaltern welche für jedes Widget durch deren Elemente ersetzt werden. Vorgabe "%parse"
							%parse Widget::parse()
							%label Widget::generateLabel()
							%field Widget::generate()
							%error Widget::getErrorAsHTML()
							%errorPlain Widget::getErrorAsString()
	array attributes:	Array mit Attributen für die Widget::parse() Methode. Z.B. array('tableless'=>true)


=== ce_formularTest ===
=======================
Inhaltselement womit das im Code-Feld eingetragenen DCA-Array gerendert wird.
NUR zu Testzwecken, niemals produktiv einsetzen!
Die Variable muss $dca lauten. Vgl. Beispiel unten.
Eine Formular-Konfiguration ist hier nicht möglich.

	
=== Beispiel ===
==================

$dca = array
(
	'Kontaktdaten' => array
	(
		'label'		=> 'Ihre Kontaktdaten',
		'inputType'	=> 'headline'
	),
	'vorname' => array
	(
		'label'		=> 'Vorname',
		'inputType' => 'text',
		'default'	=> 'vorname',
		'eval'		=> array('mandatory'=>true,'helpmessage'=>'Ihr Vorname')
	),
	'nachname' => array
	(
		'label'		=> 'Nachname',
		'inputType' => 'text',
		'default'	=> 'nachname',
		'eval'		=> array('mandatory'=>true,'helpmessage'=>'Ihr Nachname')	
	),
	'datum' => array
	(
		'label'		=> 'Datum',
		'inputType' => 'text',
		'default'	=> date('d.m.Y'),
		'eval'		=> array('datepicker'=>$this->getDatePickerString(),'cleardefault'=>false)	
	),

	'email' => array
	(
		'label'		=> 'E-Mail',
		'inputType' => 'text',
		'eval'		=> array('mandatory'=>true,'rgxp'=>'email')		
	),
	'telefon' => array
	(
		'label'		=> 'Telefon',
		'inputType' => 'text',
		'eval'		=> array('mandatory'=>true)		
	),
	'bemerkung' => array
	(
		'label'		=> 'Bemerkung',
		'inputType' => 'textarea',
		'eval'		=> array('rte'=>'tinyMCE')
	),
	'submit' => array
	(
		'label' 	=> 'speichern',
		'inputType' => 'submit'
	)
);

$frm = new Formular('a1');
$frm->setDCA($dca);	
$frm->setConfig('generateFormat','<div>%label %field %error </div>');
$frm->setConfig('attributes',array('tableless'=>true));
if($frm->isSubmitted() && $frm->validate())
{
	var_dump($frm->getData());
}
echo $frm->parse();
