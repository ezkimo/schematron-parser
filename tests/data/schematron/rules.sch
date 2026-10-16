<?xml version="1.0" encoding="UTF-8"?>
<sch:schematron xmlns:sch="http://purl.oclc.org/dsdl/schematron">
    <sch:rule context="//*">
        <sch:let name="nodeCount" value="count(//*[1])"/>
        <sch:assert test="count(//*[1]) &gt; 0">At least one node</sch:assert>
    </sch:rule>
</sch:schematron>
