<?php 

namespace AlboradaIT\LaravelProgress\Contracts;

interface ShouldTriggerProgressRecalculation
{
    public function getProgressables(): iterable;
}