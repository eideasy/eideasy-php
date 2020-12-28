# Digital Signatures and eID user identification powered by eID Easy
This package allows among others

- Create digital signatures with eID Easy service
- Create ASIC-E digital signature containers (.asice files)
- Add digital signatures in XAdES format to .asice files

## Prerequisites

Before being able to create signatures and send API calls is needed credentials from id.eideasy.com or test site test.eideasy.com

## Installation

run `composer require eideasy/eideasy-php`

## Usage

- use class  EidEasy\Signatures\Asice for creating new .asice container and adding new signatures
- use class EidEasy\Api\EidEasyApi for simplified API calls

## More info

Check https://eideasy.com and Postman documentation at https://documenter.getpostman.com/view/3869493/Szf6WoG1



