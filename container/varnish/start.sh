#!/bin/bash

varnishd -a 0.0.0.0:80 -F -f $VCL_CONFIG -s malloc,$CACHE_SIZE

varnishlog