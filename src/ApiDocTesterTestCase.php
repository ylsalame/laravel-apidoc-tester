<?php

namespace YLSalame\LaravelApiDocTester;

use Storage;
use File;
use Illuminate\Support\ServiceProvider;

class ApiDocTesterTestCase
{
    public $inputData;
    public $outputData;
    public $responseCode;
    public $stubContent;
    public $group;
    public $name;
    public $url;
    public $type;
    public $title;

    public $targetDirectory;
    public $groupCount;
    public $caseCount;

    protected $fileName;
    protected $className;
    protected $content;

    private function processParameters()
    {
        $countPrefix = substr((100000 + $this->caseCount), 1);
        $this->fileName = $countPrefix.'_'.$this->group.'_'.$this->name.'_'.$this->groupCount.'Cest.php';
        $this->className = $countPrefix.'_'.$this->group.'_'.$this->name.'_'.$this->groupCount.'Cest';
    }

    public function generate()
    {
        $this->processParameters();
        $this->parse();
        $this->save();
    }

    private function save()
    {
        Storage::put($this->targetDirectory.$this->fileName, $this->content);
    }

    private function parse()
    {
        $placeHolders = [];
        $placeHolders[] = '{{ timestamp }}';
        $placeHolders[] = '{{ title }}';
        $placeHolders[] = '{{ className }}';
        $placeHolders[] = '{{ type }}';
        $placeHolders[] = '{{ url }}';
        $placeHolders[] = '{{ responseCode }}';
        $placeHolders[] = '{{ input }}';
        $placeHolders[] = '{{ output }}';

        $values = [];
        $values[] = date('Y-m-d H:i:s');
        $values[] = $this->title;
        $values[] = $this->className;
        $values[] = $this->type;
        $values[] = $this->url;
        $values[] = $this->responseCode;
        $values[] = $this->parseInput();
        $values[] = $this->parseOutput();

        $this->content = str_replace($placeHolders, $values, $this->stubContent);
    }

    private function parseInput() : string
    {
        if (!isset($this->inputData['content'])) {
            return '[]';
        }

        $inputDataObject = json_decode($this->inputData['content']);
        if (is_object($inputDataObject)) {
            $objectVariables = get_object_vars($inputDataObject);
            return var_export($objectVariables, true);
        }

        return '[]';
    }

    private function parseOutput() : string
    {
        $output = '';
        $formatPrefix = chr(10).str_repeat(' ', 8);

        if (empty($this->outputData)) {
            return $output;
        }

        $arrayFieldsList = [];
        foreach (reset($this->outputData) as $field) {
            $fieldName = $field['field'];
            $fieldOptional = $field['optional'];
            $fieldArray = substr($field['type'], -2) == '[]' ? true : false;
            $fieldType = strtolower($field['type']);
            $fieldType = ($fieldType == 'date' ? 'string:customDateFilter' : $fieldType);
            $fieldType .= $fieldOptional ? '|null' : '';
            $fieldParent = substr($fieldName, 0, stripos($fieldName, '.'));

            $fieldPath = '['.$fieldName.']';
            $fieldPath = str_replace('.', '][', $fieldPath);
            $fieldPath = str_replace('[]]', '][*]', $fieldPath);

            if ($fieldArray) {
                $fieldPath .= '[*]';
                $arrayFieldsList[$fieldName] = $fieldPath;
            }

            $fieldCalculatedName = $fieldName;
            if (!empty($arrayFieldsList[$fieldParent])) {
                $fieldCalculatedName = substr($fieldName, strlen($fieldParent) + 1);
                $fieldPath = $arrayFieldsList[$fieldParent];
            } else {
                if (stripos($fieldName, '.') > 0) {
                    $fieldCalculatedName = substr($fieldName, stripos($fieldName, '.') + 1);
                }
                $fieldPath = substr($fieldPath, 0, strripos($fieldPath, '['));
                //$fieldPath .= ' / '.$fieldPathCalc.' - '.strripos($fieldPath, '[');
            }

            $output .= $formatPrefix.'//field '.$fieldName;
            $output .= $formatPrefix.'//    name: '.$fieldName;
            $output .= $formatPrefix.'//    optional: '.($fieldOptional ? 'true' : 'false');
            $output .= $formatPrefix.'//    array: '.($fieldArray ? 'true' : 'false');
            $output .= $formatPrefix.'//    type: '.$fieldType;
            $output .= $formatPrefix.'//    parent: '.$fieldParent;
            $output .= $formatPrefix.'//    path: [data]'.$fieldPath;

            if ($fieldArray) {
                $output .= $formatPrefix.'//verify that '.$fieldName.' (array) is not empty';
                $output .= $formatPrefix.'$objectList = $I->grabDataFromResponseByJsonPath("$.[data]'.$fieldPath.'");';
                $output .= $formatPrefix.'$this->assertNotEmpty($objectList);';
                $output .= chr(10);
            } else {
                if (!$fieldOptional) {
                    $output .= $formatPrefix.'//verify that '.$fieldName.' IS not empty and IS of the right type ('.$fieldType.')';
                    $output .= $formatPrefix.'$I->seeResponseMatchesJsonType(["'.$fieldCalculatedName.'" => "'.$fieldType.'"], "$.[data]'.$fieldPath.'");';
                    $output .= chr(10);
                } else {
                    $output .= $formatPrefix.'//verify that '.$fieldName.' IS of the right type ('.$fieldType.') IF it is not empty';
                    $output .= $formatPrefix.'$objectData = $I->grabDataFromResponseByJsonPath("$.[data]'.$fieldPath.'");';
                    $output .= $formatPrefix.'if (!empty($objectData[0]["'.$fieldCalculatedName.'"])) {';
                    $output .= $formatPrefix.chr(9).'$I->seeResponseMatchesJsonType(["'.$fieldCalculatedName.'" => "'.$fieldType.'"], "$.[data]'.$fieldPath.'");';
                    $output .= $formatPrefix.'}';
                    $output .= chr(10);
                }
            }
        }

        return $output;
    }
}
