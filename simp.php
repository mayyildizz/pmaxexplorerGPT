<?php
// Connection string
$db = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=10.0.216.33)(PORT=1521))(CONNECT_DATA=(SERVER=DEDICATED)(SERVICE_NAME=mcrspos)))" ;

// Connect to Oracle database
$conn = oci_connect('sa', 'OraS1m$1', $db);

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Prepare the query
$query = "SELECT
 BDATE.BUSINESSDATE,
 CHECKS.CHECKNUMBER,
 CHECKS.ALTERNATEID CHECKNAME,
 CASE INSTR(JRNLS.JOURNALTEXT, 'Room Charge ')
  WHEN 0 THEN TO_CHAR(TMNAME.STRINGTEXT)
  ELSE 'ROOM CHARGE : ' || TRIM(SUBSTR(TO_CHAR(JRNLS.JOURNALTEXT), INSTR(JRNLS.JOURNALTEXT, 'Room Charge ', -1, 1) + 40, 30))
 END 'ROOM / PAYMENT',
 TBLNAMES.STRINGTEXT TABLENAME,
 HUNITNAMES.STRINGTEXT REVCTR,
 MST.OBJECTNUMBER MENUITEMNUMBER,
 MSTNAMES.STRINGTEXT MENUITEMNAME,
 MGNAMES.STRINGTEXT MAJGROUP,
 FGNAMES.STRINGTEXT FAMGROUP,
 DETAIL.SALESCOUNT,
 NVL(DETAIL.TOTAL, 0) TOTAL,
 DISCOUNTS.DSCNAME,
 DISCOUNTS.REFERENCEINFO,
 DISCOUNTS.AMOUNT DSCTOTAL,
 NVL(DETAIL.TOTAL, 0) - NVL(DISCOUNTS.AMOUNT, 0) TOTAL_AFTER_DISCOUNT,
 SPNAMES.STRINGTEXT SERVINGPERIOD,
 CHECKS.CHECKOPEN,
 CHECKS.CHECKCLOSE,
 EMP.FIRSTNAME || ' ' || EMP.LASTNAME EMPNAME
FROM DBCREATE.CHECK_DETAIL DETAIL
LEFT JOIN DBCREATE.CHECKS ON CHECKS.CHECKID = DETAIL.CHECKID
LEFT JOIN
(
  SELECT
    DETAIL.CHECKDETAILID,
    MAX(MST.HIERSTRUCID) MST_HSTRUC,
    MAX(MG.HIERSTRUCID) MG_HIERSTRUCID,
    MAX(FG.HIERSTRUCID) FG_HIERSTRUCID,
    MAX(MIC.HIERSTRUCID) MIC_HIERSTRUCID,
    MAX(PC.HIERSTRUCID) PC_HIERSTRUCID,
    MAX(SL.HIERSTRUCID) SL_HIERSTRUCID
  FROM DBCREATE.CHECK_DETAIL DETAIL
  LEFT JOIN DBCREATE.CHECKS ON CHECKS.CHECKID = DETAIL.CHECKID
  LEFT JOIN DBCREATE.MENU_ITEM_MASTER MST ON MST.OBJECTNUMBER = DETAIL.OBJECTNUMBER
  LEFT JOIN DBCREATE.HIERARCHY_STRUCTURE MST_STRUC ON MST_STRUC.HIERSTRUCID = MST.HIERSTRUCID
  LEFT JOIN DBCREATE.HIERARCHY_STRUCTURE MST_STRUC_PARENT ON MST_STRUC_PARENT.HIERSTRUCID = MST_STRUC.PARENTHIERSTRUCID
  LEFT JOIN DBCREATE.MENU_ITEM_DEFINITION DEF
        ON DEF.MENUITEMMASTERID = MST.MENUITEMMASTERID
    LEFT JOIN DBCREATE.HIERARCHY_STRUCTURE DEF_STRUC
        ON DEF_STRUC.HIERSTRUCID = DEF.HIERSTRUCID
    LEFT JOIN DBCREATE.HIERARCHY_STRUCTURE DEF_STRUC_PARENT
        ON DEF_STRUC_PARENT.HIERSTRUCID = DEF_STRUC.PARENTHIERSTRUCID
    LEFT JOIN DBCREATE.MAJOR_GROUP MG   
        ON MG.OBJECTNUMBER = MST.MAJGRPOBJNUM AND
            (MG.HIERSTRUCID = DEF_STRUC.HIERSTRUCID OR 
                MG.HIERSTRUCID = DEF_STRUC_PARENT.HIERSTRUCID OR
                MG.HIERSTRUCID = DEF_STRUC_PARENT.PARENTHIERSTRUCID)
    LEFT JOIN DBCREATE.FAMILY_GROUP FG
        ON FG.OBJECTNUMBER = MST.FAMGRPOBJNUM AND
            (FG.HIERSTRUCID = DEF_STRUC.HIERSTRUCID OR 
                FG.HIERSTRUCID = DEF_STRUC_PARENT.HIERSTRUCID OR
                FG.HIERSTRUCID = DEF_STRUC_PARENT.PARENTHIERSTRUCID)
    LEFT JOIN DBCREATE.MENU_ITEM_CLASS MIC
        ON MIC.OBJECTNUMBER = DEF.MENUITEMCLASSOBJNUM AND
            (MIC.HIERSTRUCID = DEF_STRUC.HIERSTRUCID OR 
                MIC.HIERSTRUCID = DEF_STRUC_PARENT.HIERSTRUCID OR
                MIC.HIERSTRUCID = DEF_STRUC_PARENT.PARENTHIERSTRUCID)
    LEFT JOIN DBCREATE.PRINT_CLASS PC
        ON PC.OBJECTNUMBER = DEF.PRINTCLASSOBJNUM AND
            (PC.HIERSTRUCID = DEF_STRUC.HIERSTRUCID OR 
                PC.HIERSTRUCID = DEF_STRUC_PARENT.HIERSTRUCID OR
                PC.HIERSTRUCID = DEF_STRUC_PARENT.PARENTHIERSTRUCID)
    LEFT JOIN DBCREATE.SCREEN_LOOKUP SL
        ON SL.SLUINDEX = DEF.SLUINDEX AND
            SL.SLUTYPE = 6 AND
            DEVICETYPE = 1 AND
            (SL.HIERSTRUCID = DEF_STRUC.HIERSTRUCID OR 
                SL.HIERSTRUCID = DEF_STRUC_PARENT.HIERSTRUCID OR
                SL.HIERSTRUCID = DEF_STRUC_PARENT.PARENTHIERSTRUCID)
  WHERE DETAIL.DETAILTYPE = 1 AND
   (MST.HIERSTRUCID = MST_STRUC.HIERSTRUCID OR 
                MST.HIERSTRUCID = MST_STRUC_PARENT.HIERSTRUCID OR
                MST.HIERSTRUCID = MST_STRUC_PARENT.PARENTHIERSTRUCID)
  GROUP BY DETAIL.CHECKDETAILID
) LOCATIONS ON LOCATIONS.CHECKDETAILID = DETAIL.CHECKDETAILID
LEFT JOIN DBCREATE.MENU_ITEM_MASTER MST ON MST.OBJECTNUMBER = DETAIL.OBJECTNUMBER AND MST.HIERSTRUCID = LOCATIONS.MST_HSTRUC
LEFT JOIN DBCREATE.STRING_TABLE MSTNAMES ON MST.NAMEID = MSTNAMES.STRINGNUMBERID
LEFT JOIN DBCREATE.MAJOR_GROUP MG ON MG.OBJECTNUMBER = MST.MAJGRPOBJNUM AND MG.HIERSTRUCID = LOCATIONS.MG_HIERSTRUCID
LEFT JOIN DBCREATE.STRING_TABLE MGNAMES ON MG.NAMEID = MGNAMES.STRINGNUMBERID
LEFT JOIN DBCREATE.FAMILY_GROUP FG ON FG.OBJECTNUMBER = MST.FAMGRPOBJNUM AND FG.HIERSTRUCID = LOCATIONS.FG_HIERSTRUCID
LEFT JOIN DBCREATE.STRING_TABLE FGNAMES ON FG.NAMEID = FGNAMES.STRINGNUMBERID
LEFT JOIN DBCREATE.DINING_TABLE TBL ON TBL.DININGTABLEID = CHECKS.DININGTABLEID
LEFT JOIN DBCREATE.STRING_TABLE TBLNAMES ON TBLNAMES.STRINGNUMBERID = TBL.NAMEID
LEFT JOIN DBCREATE.HIERARCHY_UNIT HUNIT ON HUNIT.REVCTRID = CHECKS.REVCTRID
LEFT JOIN DBCREATE.HIERARCHY_STRUCTURE HSTRUC ON HSTRUC.HIERUNITID = HUNIT.HIERUNITID
LEFT JOIN DBCREATE.STRING_TABLE HUNITNAMES ON HUNIT.NAMEID = HUNITNAMES.STRINGNUMBERID
LEFT JOIN DBCREATE.SERVING_PERIOD SP ON SP.SERVINGPERIODID = CHECKS.SERVINGPERIODID
LEFT JOIN DBCREATE.PERIOD P ON SP.PERIODID = P.PERIODID
LEFT JOIN DBCREATE.STRING_TABLE SPNAMES ON SPNAMES.STRINGNUMBERID = P.NAMEID
LEFT JOIN EMPLOYEE EMP ON EMP.EMPLOYEEID = DETAIL.EMPLOYEEID
LEFT JOIN
(
 SELECT 
  DISCOUNTEDITEMDTL.CHECKDETAILID,
  DSCNAMES.STRINGTEXT DSCNAME,
  LOC_DETAIL.REFERENCEINFO,
  ALLOC.AMOUNT
 FROM DBCREATE.DISCOUNT_ALLOC_DETAIL ALLOC
 LEFT JOIN DBCREATE.CHECK_DETAIL FINDER ON FINDER.CHECKDETAILID = ALLOC.CHECKDETAILID
 LEFT JOIN DBCREATE.CHECK_DETAIL DSCDTL ON FINDER.CHECKID = DSCDTL.CHECKID AND DSCDTL.DETAILLINK = ALLOC.DSCNTDETAILLINK AND DSCDTL.DETAILTYPE = 2
 LEFT JOIN DBCREATE.DISCOUNT ON DISCOUNT.OBJECTNUMBER = DSCDTL.OBJECTNUMBER AND DISCOUNT.HIERSTRUCID = 2
 LEFT JOIN DBCREATE.STRING_TABLE DSCNAMES ON DSCNAMES.STRINGNUMBERID = DISCOUNT.NAMEID
 LEFT JOIN DBCREATE.CHECK_DETAIL DISCOUNTEDITEMDTL ON DISCOUNTEDITEMDTL.CHECKID = FINDER.CHECKID AND DISCOUNTEDITEMDTL.DETAILLINK = ALLOC.ITEMDETAILLINK AND DISCOUNTEDITEMDTL.DETAILTYPE = 1
 LEFT JOIN DBCREATE.CHECKS ON CHECKS.CHECKID = FINDER.CHECKID
 LEFT JOIN (SELECT * FROM LOCATION_ACTIVITY_DB.GUEST_CHECK UNION SELECT * FROM LOCATION_ACTIVITY_DB.GUEST_CHECK_HIST) LOC_CHECKS ON CHECKS.CHECKNUMBER = LOC_CHECKS.CHECKNUM AND TO_CHAR(CHECKS.CHECKOPEN + 6/24, 'DD-MON-YY HH24:MI') = TO_CHAR(LOC_CHECKS.OPENDATETIME, 'DD-MON-YY HH24:MI')
 LEFT JOIN (SELECT * FROM LOCATION_ACTIVITY_DB.GUEST_CHECK_LINE_ITEM UNION SELECT * FROM LOCATION_ACTIVITY_DB.GUEST_CHECK_LINE_ITEM_HIST) LOC_DETAIL ON LOC_DETAIL.DTLID = DSCDTL.DETAILLINK AND LOC_DETAIL.GUESTCHECKID = LOC_CHECKS.GUESTCHECKID
 WHERE DISCOUNTEDITEMDTL.VOIDLINK IS NULL AND
    DSCDTL.VOIDLINK IS NULL
) DISCOUNTS ON DISCOUNTS.CHECKDETAILID = DETAIL.CHECKDETAILID
LEFT JOIN DBCREATE.PERIOD_INSTANCE BDATE 
 ON BDATE.STARTTIME <= CHECKS.CHECKOPEN 
 AND (BDATE.ENDTIME >= CHECKS.CHECKOPEN OR BDATE.LOCALENDTIME IS NULL)
 AND BDATE.HIERSTRUCID = HSTRUC.HIERSTRUCID
 AND BDATE.BUSINESSDATE IS NOT NULL
LEFT JOIN
(
  SELECT
    MAX(JR.POSJOURNALLOGID) JRNLID,
    JR.CHECKNUM
  FROM DBCREATE.POS_JOURNAL_LOG JR
  WHERE JR.TYPE = 1
  GROUP BY JR.CHECKNUM
) JRNLFINDER ON JRNLFINDER.CHECKNUM = CHECKS.CHECKNUMBER
LEFT JOIN DBCREATE.POS_JOURNAL_LOG JRNLS ON JRNLS.POSJOURNALLOGID = JRNLFINDER.JRNLID
LEFT JOIN DBCREATE.CHECK_DETAIL TMFINDER ON TMFINDER.DETAILTYPE = 4 AND TMFINDER.CHECKID = CHECKS.CHECKID AND TMFINDER.VOIDLINK IS NULL
LEFT JOIN DBCREATE.TENDER_MEDIA TM ON TM.OBJECTNUMBER = TMFINDER.OBJECTNUMBER
LEFT JOIN DBCREATE.STRING_TABLE TMNAME ON TM.NAMEID = TMNAME.STRINGNUMBERID
WHERE DETAIL.DETAILTYPE = 1 AND 
  DETAIL.VOIDLINK IS NULL AND
  TRUNC(BDATE.BUSINESSDATE) >= TO_DATE(:start_date, 'DD-MM-YYYY') AND 
  TRUNC(BDATE.BUSINESSDATE) <= TO_DATE(:end_date, 'DD	-MM-YYYY') AND 
  CHECKS.ADDEDTOCHECKNUM IS NULL AND 
  CHECKS.REOPENEDTOCHECKNUM IS NULL AND 
  CHECKS.CHECKCLOSE IS NOT NULL";
$stid = oci_parse($conn, $query);

// Execute the query
oci_execute($stid);
?>

<!-- HTML form for date input -->
<form method="post" action="">
    Start Date: <input type="date" name="start_date">
    End Date: <input type="date" name="end_date">
    <input type="submit" name="submit" value="Execute Query">
</form>

<!-- Table to display query results -->
<table border="1">
    <?php
    // Fetch the results
    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        echo "<tr>\n";
        foreach ($row as $item) {
            echo "    <td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "&nbsp;") . "</td>\n";
        }
        echo "</tr>\n";
    }
    ?>
</table>

<?php
// Free the statement identifier when closing the connection
oci_free_statement($stid);
oci_close($conn);
?>
