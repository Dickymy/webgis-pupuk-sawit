import os
import re

files_to_clean = [
    'tests/Feature/DoubleSubmitAnalysisTest.php',
    'tests/Feature/ForwardChainingFixpointTest.php',
    'tests/Feature/ProgramFingerprintTest.php',
    'tests/Feature/RbsProgramIntegrationTest.php',
    'tests/Support/LegacySchemaBuilder.php',
    'tests/Feature/TrueLegacySchemaUpgradeTest.php',
    'tests/Support/LegacyDatabaseFixture.php',
    'tests/Unit/SupportingFertilizerSanitizerTest.php',
    'database/factories/KondisiLahanFactory.php',
    'database/seeders/DemoSawitGisSeeder.php',
    'tests/Feature/RuleBaseManagementTest.php',
    'tests/Feature/AcademicRuleEvidencePolicyTest.php',
]

base_dir = r'e:\Skripsi\Aplikasi Skripsi'

for rel_path in files_to_clean:
    path = os.path.join(base_dir, rel_path)
    if os.path.exists(path):
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        content = re.sub(r"^\s*'ph_tanah'\s*=>\s*.*,\r?\n", '', content, flags=re.MULTILINE)
        content = re.sub(r"^\s*'dosis_anjuran'\s*=>\s*.*,\r?\n", '', content, flags=re.MULTILINE)
        
        if 'RuleBaseManagementTest' in rel_path:
            content = re.sub(r"^\s*\$payload\['dosis_anjuran'\].*\r?\n", '', content, flags=re.MULTILINE)
            content = re.sub(r"^\s*\$this->assertSame.*\$rule->dosis_anjuran\);\r?\n", '', content, flags=re.MULTILINE)
            content = re.sub(r"^\s*\$this->assertStringNotContainsString.*\$rule->dosis_anjuran\);\r?\n", '', content, flags=re.MULTILINE)
            
        if 'AcademicRuleEvidencePolicyTest' in rel_path:
            content = content.replace(", 'dosis_anjuran'", "")
        
        with open(path, 'w', encoding='utf-8', newline='') as f:
            f.write(content)
